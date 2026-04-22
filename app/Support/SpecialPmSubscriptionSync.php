<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

class SpecialPmSubscriptionSync
{
    public static function sync(User $user, array $subscription): void
    {
        $status = (string) ($subscription['status'] ?? 'inactive');
        $priceId = (string) data_get($subscription, 'items.data.0.price.id', '');
        $periodEndTs = data_get($subscription, 'current_period_end');

        $user->forceFill([
            'stripe_subscription_id' => $subscription['id'] ?? $user->stripe_subscription_id,
            'stripe_price_id' => $priceId !== '' ? $priceId : $user->stripe_price_id,
            'subscription_status' => $status,
            'subscription_current_period_end' => $periodEndTs ? Carbon::createFromTimestamp((int) $periodEndTs) : null,
        ])->save();
    }

    public static function markCanceled(User $user): void
    {
        $user->forceFill([
            'subscription_status' => 'canceled',
        ])->save();
    }
}
