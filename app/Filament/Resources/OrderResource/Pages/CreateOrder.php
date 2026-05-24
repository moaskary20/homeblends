<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Order\AdminOrderService;
use App\Services\Shipping\ShippingService;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = OrderResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'customer_type' => 'registered',
            'billing_same_as_shipping' => true,
            'decrement_stock' => true,
            'send_notification' => true,
            'payment_status' => 'paid',
            'payment_method' => 'cash_on_delivery',
            'status' => OrderStatus::Confirmed->value,
            'shipping_country' => 'EG',
            'items' => [
                ['quantity' => 1],
            ],
        ]);
    }

    protected function getSteps(): array
    {
        return [
            Step::make(__('ecommerce.wizard_customer'))
                ->description(__('ecommerce.wizard_customer_desc'))
                ->schema($this->getCustomerSchema()),
            Step::make(__('ecommerce.wizard_products'))
                ->description(__('ecommerce.wizard_products_desc'))
                ->schema($this->getProductsSchema()),
            Step::make(__('ecommerce.wizard_shipping_payment'))
                ->description(__('ecommerce.wizard_shipping_payment_desc'))
                ->schema($this->getShippingPaymentSchema()),
            Step::make(__('ecommerce.wizard_review'))
                ->description(__('ecommerce.wizard_review_desc'))
                ->schema($this->getReviewSchema()),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function getCustomerSchema(): array
    {
        return [
            Forms\Components\Radio::make('customer_type')
                ->label(__('ecommerce.customer_type'))
                ->options([
                    'registered' => __('ecommerce.registered_customer'),
                    'guest' => __('ecommerce.guest_customer'),
                ])
                ->default('registered')
                ->live(),
            Forms\Components\Select::make('user_id')
                ->label(__('ecommerce.customer'))
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    return User::query()
                        ->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all();
                })
                ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                ->visible(fn (Get $get): bool => $get('customer_type') === 'registered')
                ->required(fn (Get $get): bool => $get('customer_type') === 'registered')
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if (! $user = User::find($state)) {
                        return;
                    }
                    $set('shipping_name', $user->name);
                    $set('shipping_phone', $user->phone ?? '');
                    $set('shipping_email', $user->email);
                }),
            Forms\Components\TextInput::make('guest_name')
                ->label(__('ecommerce.name'))
                ->visible(fn (Get $get): bool => $get('customer_type') === 'guest')
                ->required(fn (Get $get): bool => $get('customer_type') === 'guest'),
            Forms\Components\TextInput::make('guest_phone')
                ->label(__('ecommerce.phone'))
                ->tel()
                ->visible(fn (Get $get): bool => $get('customer_type') === 'guest')
                ->required(fn (Get $get): bool => $get('customer_type') === 'guest'),
            Forms\Components\TextInput::make('guest_email')
                ->label(__('ecommerce.email'))
                ->email()
                ->visible(fn (Get $get): bool => $get('customer_type') === 'guest'),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function getProductsSchema(): array
    {
        return [
            Forms\Components\Repeater::make('items')
                ->label(__('ecommerce.order_items'))
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label(__('ecommerce.product'))
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            return Product::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->name)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            $set('product_variant_id', null);
                            if ($product = Product::find($state)) {
                                $set('unit_price', $product->effective_price);
                            }
                        }),
                    Forms\Components\Select::make('product_variant_id')
                        ->label(__('ecommerce.variants'))
                        ->options(function (Get $get): array {
                            $productId = $get('product_id');
                            if (! $productId) {
                                return [];
                            }

                            return ProductVariant::query()
                                ->where('product_id', $productId)
                                ->pluck('sku', 'id')
                                ->all();
                        })
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                            if ($variant = ProductVariant::find($state)) {
                                $set('unit_price', $variant->price);

                                return;
                            }
                            if ($product = Product::find($get('product_id'))) {
                                $set('unit_price', $product->effective_price);
                            }
                        }),
                    Forms\Components\TextInput::make('quantity')
                        ->label(__('ecommerce.quantity'))
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    Forms\Components\TextInput::make('unit_price')
                        ->label(__('ecommerce.unit_price'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('ج.م')
                        ->required(),
                ])
                ->columns(4)
                ->defaultItems(1)
                ->addActionLabel(__('ecommerce.add_product'))
                ->minItems(1)
                ->collapsible(),
            Forms\Components\Toggle::make('decrement_stock')
                ->label(__('ecommerce.decrement_stock'))
                ->default(true),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function getShippingPaymentSchema(): array
    {
        return [
            Forms\Components\Section::make(__('ecommerce.shipping_address'))
                ->schema([
                    Forms\Components\TextInput::make('shipping_name')
                        ->label(__('ecommerce.name'))
                        ->required(),
                    Forms\Components\TextInput::make('shipping_phone')
                        ->label(__('ecommerce.phone'))
                        ->tel()
                        ->required(),
                    Forms\Components\TextInput::make('shipping_email')
                        ->label(__('ecommerce.email'))
                        ->email(),
                    Forms\Components\TextInput::make('shipping_city')
                        ->label(__('ecommerce.city'))
                        ->required(),
                    Forms\Components\Textarea::make('shipping_address_line')
                        ->label(__('ecommerce.address'))
                        ->required()
                        ->rows(2),
                    Forms\Components\TextInput::make('shipping_postal_code')
                        ->label(__('ecommerce.postal_code')),
                    Forms\Components\TextInput::make('shipping_country')
                        ->label(__('ecommerce.country'))
                        ->default('EG')
                        ->maxLength(2)
                        ->live(),
                ])
                ->columns(2),
            Forms\Components\Toggle::make('billing_same_as_shipping')
                ->label(__('ecommerce.billing_same_as_shipping'))
                ->default(true)
                ->live(),
            Forms\Components\Section::make(__('ecommerce.shipping'))
                ->schema([
                    Forms\Components\Select::make('shipping_rate_id')
                        ->label(__('ecommerce.shipping_method'))
                        ->options(fn (Get $get): array => $this->getShippingRateOptions($get))
                        ->searchable()
                        ->required(fn (Get $get): bool => ! $get('manual_free_shipping')),
                    Forms\Components\Toggle::make('manual_free_shipping')
                        ->label(__('ecommerce.manual_free_shipping'))
                        ->live(),
                    Forms\Components\TextInput::make('coupon_code')
                        ->label(__('ecommerce.coupon_code'))
                        ->visible(fn (Get $get): bool => $get('customer_type') === 'registered'),
                    Forms\Components\TextInput::make('manual_discount')
                        ->label(__('ecommerce.manual_discount'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->prefix('ج.م'),
                ])
                ->columns(2),
            Forms\Components\Section::make(__('ecommerce.payment'))
                ->schema([
                    Forms\Components\Select::make('payment_method')
                        ->label(__('ecommerce.payment_method'))
                        ->options(function (): array {
                            $gateways = PaymentGateway::query()
                                ->active()
                                ->orderBy('sort_order')
                                ->pluck('name', 'code')
                                ->all();

                            return array_merge($gateways, [
                                'cash' => __('ecommerce.payment_cash'),
                                'bank_transfer' => __('ecommerce.payment_bank_transfer'),
                            ]);
                        })
                        ->required(),
                    Forms\Components\Select::make('payment_status')
                        ->label(__('ecommerce.payment_status'))
                        ->options([
                            'pending' => __('ecommerce.payment_pending'),
                            'paid' => __('ecommerce.payment_paid'),
                        ])
                        ->required()
                        ->live(),
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label(__('ecommerce.paid_at'))
                        ->visible(fn (Get $get): bool => $get('payment_status') === 'paid')
                        ->default(now()),
                    Forms\Components\Select::make('status')
                        ->label(__('ecommerce.status'))
                        ->options(collect(OrderStatus::cases())->mapWithKeys(
                            fn (OrderStatus $s) => [$s->value => $s->label()]
                        ))
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label(__('ecommerce.notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('send_notification')
                        ->label(__('ecommerce.send_order_notification'))
                        ->default(true),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected function getReviewSchema(): array
    {
        return [
            Forms\Components\Placeholder::make('order_summary')
                ->label(__('ecommerce.order_summary'))
                ->content(function (Get $get) {
                    $data = $get();
                    $preview = app(AdminOrderService::class)->preview($data);

                    return view('filament.orders.create-summary', [
                        'preview' => $preview,
                        'data' => $data,
                    ]);
                })
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int|string, string>
     */
    protected function getShippingRateOptions(Get $get): array
    {
        if ($get('manual_free_shipping')) {
            return [];
        }

        $preview = app(AdminOrderService::class)->preview($get());

        return app(ShippingService::class)
            ->getAvailableRates(
                strtoupper($get('shipping_country') ?? 'EG'),
                $preview['subtotal'],
                $preview['weight']
            )
            ->mapWithKeys(fn ($rate) => [
                $rate->id => $rate->name.' — '.number_format((float) $rate->rate, 2).' ج.م',
            ])
            ->all();
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(AdminOrderService::class)->create($data, auth()->user());
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('ecommerce.order_created_success');
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
