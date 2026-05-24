<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Concerns\HasInvoiceActions;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Order\OrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use HasInvoiceActions;

    protected static string $resource = OrderResource::class;

    protected ?string $previousStatus = null;

    protected ?string $previousTracking = null;

    protected function getHeaderActions(): array
    {
        return [
            ...static::getInvoiceHeaderActions(),
            Actions\ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousStatus = $this->record->status->value;
        $this->previousTracking = $this->record->tracking_number;

        return $data;
    }

    protected function afterSave(): void
    {
        $order = $this->record->fresh();
        $service = app(OrderService::class);

        if ($this->previousStatus !== $order->status->value) {
            $order->statusHistory()->create([
                'status' => $order->status->value,
                'comment' => __('ecommerce.status_updated_from_admin'),
                'user_id' => auth()->id(),
            ]);

            if ($order->status === OrderStatus::Refunded) {
                $order->update(['payment_status' => 'refunded']);
            }

            app(NotificationDispatcher::class)->orderStatusUpdated(
                $order->fresh(['user']),
                __('ecommerce.status_updated_from_admin')
            );
        }

        if (
            $order->tracking_number
            && $order->tracking_number !== $this->previousTracking
            && in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)
        ) {
            $service->updateStatus(
                $order,
                OrderStatus::Shipped,
                __('ecommerce.tracking_number_added', ['number' => $order->tracking_number]),
                auth()->user()
            );
        } elseif ($order->tracking_number && $order->tracking_number !== $this->previousTracking) {
            $order->statusHistory()->create([
                'status' => $order->status->value,
                'comment' => __('ecommerce.tracking_number_updated', ['number' => $order->tracking_number]),
                'user_id' => auth()->id(),
            ]);
        }
    }
}
