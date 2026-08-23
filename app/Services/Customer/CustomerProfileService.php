<?php

namespace App\Services\Customer;

use App\Models\Address;
use App\Models\User;

class CustomerProfileService
{
    public function syncFromCheckout(User $user, array $shippingAddress): Address
    {
        $normalized = $this->normalizeShippingAddress($shippingAddress);

        $user->update([
            'phone' => $normalized['phone'],
            'alternate_phone' => $normalized['alternate_phone'],
        ]);

        $addressPayload = [
            'label' => __('ecommerce.default_shipping_address'),
            'first_name' => $normalized['first_name'],
            'last_name' => $normalized['last_name'],
            'phone' => $normalized['phone'],
            'alternate_phone' => $normalized['alternate_phone'],
            'address_line_1' => $normalized['address_line_1'],
            'address_line_2' => $normalized['address_line_2'],
            'city' => $normalized['city'],
            'state' => $normalized['state'],
            'postal_code' => $normalized['postal_code'],
            'country' => $normalized['country'],
            'is_default' => true,
        ];

        $defaultAddress = $user->addresses()->where('is_default', true)->first();

        if ($defaultAddress) {
            $defaultAddress->update($addressPayload);
            $user->addresses()->where('id', '!=', $defaultAddress->id)->update(['is_default' => false]);

            return $defaultAddress->fresh();
        }

        $user->addresses()->update(['is_default' => false]);

        return $user->addresses()->create($addressPayload);
    }

    public function normalizeShippingAddress(array $shippingAddress): array
    {
        return [
            'first_name' => trim((string) ($shippingAddress['first_name'] ?? '')),
            'last_name' => trim((string) ($shippingAddress['last_name'] ?? '')),
            'phone' => trim((string) ($shippingAddress['phone'] ?? '')),
            'alternate_phone' => filled($shippingAddress['alternate_phone'] ?? null)
                ? trim((string) $shippingAddress['alternate_phone'])
                : null,
            'address_line_1' => trim((string) ($shippingAddress['address_line_1'] ?? '')),
            'address_line_2' => filled($shippingAddress['address_line_2'] ?? null)
                ? trim((string) $shippingAddress['address_line_2'])
                : null,
            'city' => trim((string) ($shippingAddress['city'] ?? '')),
            'state' => filled($shippingAddress['state'] ?? null)
                ? trim((string) $shippingAddress['state'])
                : null,
            'postal_code' => filled($shippingAddress['postal_code'] ?? null)
                ? trim((string) $shippingAddress['postal_code'])
                : null,
            'country' => strtoupper(trim((string) ($shippingAddress['country'] ?? 'EG'))) ?: 'EG',
        ];
    }
}
