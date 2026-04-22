<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SpecialPmSubscriptionSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $endpointSecret = (string) config('services.stripe.webhook_secret');
        $secret = (string) config('services.stripe.secret');

        try {
            if ($endpointSecret !== '') {
                $event = Webhook::constructEvent($payload, $signature, $endpointSecret);
            } else {
                $event = Event::constructFrom(json_decode($payload, true) ?: []);
            }
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature/payload failed', ['error' => $e->getMessage()]);
            return response()->json(['received' => false], 400);
        }

        $type = (string) $event->type;
        $object = (array) ($event->data->object ?? []);

        if ($type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($object, $secret);
        }

        if (in_array($type, ['customer.subscription.created', 'customer.subscription.updated', 'customer.subscription.deleted'], true)) {
            $this->handleSubscriptionEvent($type, $object);
        }

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted(array $session, string $secret): void
    {
        if (($session['mode'] ?? '') !== 'subscription') {
            return;
        }

        $user = null;
        $metaUserId = data_get($session, 'metadata.user_id');
        $customerId = (string) ($session['customer'] ?? '');

        if ($metaUserId) {
            $user = User::find((int) $metaUserId);
        }

        if (!$user && $customerId !== '') {
            $user = User::where('stripe_customer_id', $customerId)->first();
        }

        if (!$user) {
            return;
        }

        if ($customerId !== '' && $user->stripe_customer_id !== $customerId) {
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $subscriptionId = (string) ($session['subscription'] ?? '');
        if ($subscriptionId === '' || $secret === '') {
            return;
        }

        try {
            $stripe = new StripeClient($secret);
            $subscription = $stripe->subscriptions->retrieve($subscriptionId, []);
            SpecialPmSubscriptionSync::sync($user, $subscription->toArray());
        } catch (\Throwable $e) {
            Log::warning('Stripe checkout session sync failed', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleSubscriptionEvent(string $type, array $subscription): void
    {
        $customerId = (string) ($subscription['customer'] ?? '');
        if ($customerId === '') {
            return;
        }

        $user = User::where('stripe_customer_id', $customerId)->first();
        if (!$user) {
            return;
        }

        if ($type === 'customer.subscription.deleted') {
            SpecialPmSubscriptionSync::markCanceled($user);
            return;
        }

        SpecialPmSubscriptionSync::sync($user, $subscription);
    }
}
