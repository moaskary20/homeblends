<?php

namespace App\Models;

use App\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasSlug;

    /** @var list<int> */
    public const PLAN_OPTIONS = [3, 6, 9, 12, 18, 24, 36];

    protected $fillable = [
        'name', 'slug', 'description', 'banner_image', 'gallery',
        'starts_at', 'ends_at', 'is_active', 'installment_months', 'installment_plans', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'gallery' => 'array',
            'installment_months' => 'integer',
            'installment_plans' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Offer $offer): void {
            $plans = $offer->planMonths();
            $offer->installment_plans = $plans;
            $offer->installment_months = $plans[0] ?? 6;
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(OfferProduct::class)->orderBy('sort_order');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(InstallmentContract::class);
    }

    public function scopeActive($query)
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->is_active
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }

    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at > now();
    }

    public function isEnded(): bool
    {
        return $this->ends_at < now();
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return __('ecommerce.offer_inactive');
        }

        if ($this->isRunning()) {
            return __('ecommerce.offer_running');
        }

        if ($this->isUpcoming()) {
            return __('ecommerce.offer_upcoming');
        }

        return __('ecommerce.offer_ended');
    }

    public function offerTotal(): float
    {
        return round((float) $this->products->sum(fn (OfferProduct $entry) => (float) $entry->offer_price), 2);
    }

    public function compareTotal(): float
    {
        return round((float) $this->products->sum(fn (OfferProduct $entry) => $entry->comparePrice()), 2);
    }

    public function canPurchaseAsSet(): bool
    {
        if ($this->products->isEmpty()) {
            return false;
        }

        return $this->products->every(
            fn (OfferProduct $entry) => $entry->product && $entry->hasStock()
        );
    }

    /**
     * @return list<int>
     */
    public function planMonths(): array
    {
        return self::normalizePlanMonths($this->installment_plans, $this->attributes['installment_months'] ?? $this->installment_months);
    }

    public function defaultPlanMonths(): int
    {
        return $this->planMonths()[0] ?? 6;
    }

    public function hasPlan(int $months): bool
    {
        return in_array($months, $this->planMonths(), true);
    }

    /**
     * @return list<array{months: int, monthly_amount: float}>
     */
    public function plansForTotal(?float $total = null): array
    {
        $total ??= $this->offerTotal();

        return array_map(
            fn (int $months) => [
                'months' => $months,
                'monthly_amount' => $this->monthlyAmountFor($total, $months),
            ],
            $this->planMonths()
        );
    }

    public function plansLabel(): string
    {
        $plans = $this->planMonths();

        if (count($plans) <= 1) {
            return __('ecommerce.installment_months_badge', ['count' => $plans[0] ?? $this->defaultPlanMonths()]);
        }

        return __('ecommerce.installment_plans_badge', ['plans' => implode(' / ', $plans)]);
    }

    public function monthlyAmountFor(float $total, ?int $months = null): float
    {
        $months = max(1, $months ?? $this->defaultPlanMonths());

        return round($total / $months, 2);
    }

    public function lowestMonthlyAmount(?float $total = null): float
    {
        $plans = $this->planMonths();
        $longest = $plans[array_key_last($plans)] ?? $this->defaultPlanMonths();

        return $this->monthlyAmountFor($total ?? $this->offerTotal(), $longest);
    }

    /**
     * @return array<int, string>
     */
    public static function planFormOptions(?self $record = null): array
    {
        $months = self::PLAN_OPTIONS;

        if ($record) {
            $months = array_values(array_unique([...$months, ...$record->planMonths()]));
            sort($months);
        }

        return collect($months)
            ->mapWithKeys(fn (int $value) => [$value => __('ecommerce.installment_plan_option', ['count' => $value])])
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function normalizePlanMonths(mixed $plans, mixed $fallback = null): array
    {
        $values = collect(is_array($plans) ? $plans : [])
            ->map(function ($value): int {
                if (is_array($value)) {
                    $value = $value['months'] ?? $value['value'] ?? 0;
                }

                return (int) $value;
            })
            ->filter(fn (int $months) => $months >= 2 && $months <= 36)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($values !== []) {
            return $values;
        }

        $fallbackMonths = (int) $fallback;

        return $fallbackMonths >= 2 ? [$fallbackMonths] : [6];
    }

    /**
     * @return list<string>
     */
    public function galleryPaths(): array
    {
        $gallery = $this->gallery ?? [];

        if (! is_array($gallery)) {
            return [];
        }

        return array_values(array_filter($gallery, fn ($path) => is_string($path) && $path !== ''));
    }
}
