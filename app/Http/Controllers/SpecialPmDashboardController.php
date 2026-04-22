<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\SpecialPmSubscriptionSync;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\InviteUserMail;
use Illuminate\Support\Facades\URL;
use Stripe\StripeClient;

class SpecialPmDashboardController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if (!$this->isSubscriptionActive($user)) {
            return redirect()->route('special-pm.billing')->with('status', 'Subscription required. Scan the QR code on Billing to unlock full access.');
        }

        $projectIds = Project::where('created_by', $user->id)->pluck('id');
        $totalProjects = $projectIds->count();
        $activeProjects = Project::whereIn('id', $projectIds)->where('status', 'active')->count();
        $totalTasks = Task::whereIn('project_id', $projectIds)->count();
        $completedTasks = Task::whereIn('project_id', $projectIds)
            ->where(function ($query) {
                $query->where('status', 'completed')->orWhere('progress', 100);
            })
            ->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        $subscriptionActive = $this->isSubscriptionActive($user);

        return view('special_pm.dashboard', compact(
            'user',
            'totalProjects',
            'activeProjects',
            'totalTasks',
            'completedTasks',
            'completionRate',
            'subscriptionActive'
        ));
    }

    public function billing()
    {
        $user = auth()->user();
        $subscriptionActive = $this->isSubscriptionActive($user);
        $checkoutUrl = null;
        $checkoutError = null;
        $dummyActivateUrl = null;

        if (!$subscriptionActive) {
            $checkoutResult = $this->createCheckoutSession($user);
            $checkoutUrl = $checkoutResult['url'];
            $checkoutError = $checkoutResult['error'];

            if (app()->environment('local')) {
                $dummyActivateUrl = URL::temporarySignedRoute(
                    'special-pm.billing.dummy-activate',
                    now()->addMinutes(30),
                    ['user' => $user->id]
                );
            }
        }

        $billingData = [
            'price_id' => config('services.stripe.special_pm_price_id'),
            'status' => $user->subscription_status ?? 'inactive',
            'customer_id' => $user->stripe_customer_id,
            'subscription_id' => $user->stripe_subscription_id,
            'current_period_end' => $user->subscription_current_period_end,
            'checkout_url' => $checkoutUrl,
            'checkout_qr_url' => $checkoutUrl
                ? 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($checkoutUrl)
                : null,
            'checkout_error' => $checkoutError,
            'subscription_active' => $subscriptionActive,
            'dummy_activate_url' => $dummyActivateUrl,
            'dummy_qr_url' => $dummyActivateUrl
                ? 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . urlencode($dummyActivateUrl)
                : null,
        ];

        return view('special_pm.billing', compact('user', 'billingData'));
    }

    public function settings()
    {
        $user = auth()->user();
        $subscriptionActive = $this->isSubscriptionActive($user);

        return view('special_pm.settings', compact('user', 'subscriptionActive'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'white_label_brand_name' => ['nullable', 'string', 'max:255'],
            'white_label_logo_url' => ['nullable', 'url', 'max:2048'],
            'white_label_primary_color' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'white_label_accent_color' => ['required', 'regex:/^#(?:[0-9a-fA-F]{3}){1,2}$/'],
            'company' => ['required', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'white_label_brand_name' => $validated['white_label_brand_name'] ?? null,
            'white_label_logo_url' => $validated['white_label_logo_url'] ?? null,
            'white_label_primary_color' => $validated['white_label_primary_color'],
            'white_label_accent_color' => $validated['white_label_accent_color'],
            'company' => $validated['company'],
        ])->save();

        ActivityLog::record(
            'updated_special_pm_branding',
            'Updated white-label settings for Special PM dashboard',
            $user
        );

        return redirect()->route('special-pm.settings')->with('status', 'White-label settings updated.');
    }

    public function manageDm()
    {
        $user = auth()->user();

        if (!$this->isSubscriptionActive($user)) {
            return redirect()->route('special-pm.billing')->with('status', 'Manage DM is locked until subscription payment is active.');
        }

        $dms = User::query()
            ->where('role', 'dm')
            ->where('company', $user->company)
            ->orderBy('name')
            ->get();

        $subscriptionActive = true;

        return view('special_pm.manage_dm', compact('user', 'dms', 'subscriptionActive'));
    }

    public function inviteDm(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$this->isSubscriptionActive($user)) {
            return redirect()->route('special-pm.billing')->with('status', 'Manage DM is locked until subscription payment is active.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $inviteUrl = route('register', [
            'email' => $validated['email'],
            'role' => 'dm',
            'company' => $user->company,
        ]);

        Mail::to($validated['email'])->send(new InviteUserMail($inviteUrl, 'dm'));

        ActivityLog::record(
            'invited_dm_from_special_pm',
            'Invited DM user ' . $validated['email'] . ' under company ' . ($user->company ?: 'N/A'),
            $user
        );

        return redirect()->route('special-pm.manage-dm')->with('status', 'DM invite sent to ' . $validated['email']);
    }

    public function destroyDm(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if (!$this->isSubscriptionActive($actor)) {
            return redirect()->route('special-pm.billing')->with('status', 'Manage DM is locked until subscription payment is active.');
        }

        if ($user->role !== 'dm' || $user->company !== $actor->company) {
            return redirect()->route('special-pm.manage-dm')->with('status', 'You can only remove DMs in your own company.');
        }

        $deletedEmail = $user->email;
        $deletedName = $user->name;
        $user->delete();

        ActivityLog::record(
            'deleted_dm_from_special_pm',
            'Removed DM user ' . $deletedName . ' (' . $deletedEmail . ') from Special PM workspace',
            $actor
        );

        return redirect()->route('special-pm.manage-dm')->with('status', 'DM user removed successfully.');
    }

    private function isSubscriptionActive(User $user): bool
    {
        return in_array((string) $user->subscription_status, ['active', 'trialing'], true);
    }

    private function createCheckoutSession(User $user): array
    {
        $secret = (string) config('services.stripe.secret');
        $priceId = (string) config('services.stripe.special_pm_price_id');

        if ($secret === '' || $priceId === '') {
            return [
                'url' => null,
                'error' => 'Stripe is not configured. Set STRIPE_SECRET and STRIPE_SPECIAL_PM_PRICE_ID.',
            ];
        }

        try {
            $stripe = new StripeClient($secret);

            if (empty($user->stripe_customer_id)) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'role' => (string) $user->role,
                    ],
                ]);

                $user->forceFill([
                    'stripe_customer_id' => $customer->id,
                ])->save();
            }

            $session = $stripe->checkout->sessions->create([
                'mode' => 'subscription',
                'customer' => $user->stripe_customer_id,
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => route('special-pm.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('special-pm.billing'),
                'allow_promotion_codes' => true,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan' => 'special_pm_monthly',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'plan' => 'special_pm_monthly',
                    ],
                ],
            ]);

            return [
                'url' => $session->url,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'url' => null,
                'error' => 'Unable to create Stripe Checkout session: ' . $e->getMessage(),
            ];
        }
    }

    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = (string) config('services.stripe.secret');
        $priceId = (string) config('services.stripe.special_pm_price_id');

        if ($secret === '' || $priceId === '') {
            return back()->with('status', 'Stripe is not configured. Set STRIPE_SECRET and STRIPE_SPECIAL_PM_PRICE_ID.');
        }

        try {
            $stripe = new StripeClient($secret);

            if (empty($user->stripe_customer_id)) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'role' => (string) $user->role,
                    ],
                ]);

                $user->forceFill([
                    'stripe_customer_id' => $customer->id,
                ])->save();
            }

            $session = $stripe->checkout->sessions->create([
                'mode' => 'subscription',
                'customer' => $user->stripe_customer_id,
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => route('special-pm.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('special-pm.billing'),
                'allow_promotion_codes' => true,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan' => 'special_pm_monthly',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => (string) $user->id,
                        'plan' => 'special_pm_monthly',
                    ],
                ],
            ]);

            return redirect()->away($session->url);
        } catch (\Throwable $e) {
            return back()->with('status', 'Unable to create Stripe Checkout session: ' . $e->getMessage());
        }
    }

    public function success(Request $request): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        $user = $request->user();
        $secret = (string) config('services.stripe.secret');

        if ($sessionId === '' || $secret === '') {
            return redirect()->route('special-pm.billing')->with('status', 'Payment session verification skipped. Check Stripe configuration.');
        }

        try {
            $stripe = new StripeClient($secret);
            $session = $stripe->checkout->sessions->retrieve($sessionId, []);

            if (!empty($session->customer) && empty($user->stripe_customer_id)) {
                $user->forceFill(['stripe_customer_id' => (string) $session->customer])->save();
            }

            if (!empty($session->subscription)) {
                $subscription = $stripe->subscriptions->retrieve((string) $session->subscription, []);
                SpecialPmSubscriptionSync::sync($user, $subscription->toArray());

                ActivityLog::record(
                    'special_pm_subscription_started',
                    'Started Special PM monthly Stripe subscription [' . $subscription->id . ']',
                    $user
                );
            }

            return redirect()->route('special-pm.billing')->with('status', 'Subscription activated successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('special-pm.billing')->with('status', 'Subscription activation pending webhook sync: ' . $e->getMessage());
        }
    }

    public function refreshSubscription(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            return redirect()->route('special-pm.billing')->with('status', 'Stripe is not configured. Set STRIPE_SECRET.');
        }

        if (empty($user->stripe_customer_id) && empty($user->stripe_subscription_id)) {
            return redirect()->route('special-pm.billing')->with('status', 'No Stripe customer/subscription found yet. Complete payment first.');
        }

        try {
            $stripe = new StripeClient($secret);
            $subscription = null;

            if (!empty($user->stripe_subscription_id)) {
                $subscription = $stripe->subscriptions->retrieve((string) $user->stripe_subscription_id, []);
            } elseif (!empty($user->stripe_customer_id)) {
                $list = $stripe->subscriptions->all([
                    'customer' => (string) $user->stripe_customer_id,
                    'status' => 'all',
                    'limit' => 10,
                ]);

                if (!empty($list->data)) {
                    $active = collect($list->data)->first(function ($sub) {
                        return in_array((string) ($sub->status ?? ''), ['active', 'trialing'], true);
                    });
                    $subscription = $active ?: $list->data[0];
                }
            }

            if (!$subscription) {
                $user->forceFill([
                    'subscription_status' => 'inactive',
                ])->save();

                return redirect()->route('special-pm.billing')->with('status', 'No Stripe subscription found yet.');
            }

            SpecialPmSubscriptionSync::sync($user, $subscription->toArray());

            $isActive = $this->isSubscriptionActive($user->fresh());
            return redirect()->route('special-pm.billing')->with('status', $isActive
                ? 'Subscription is active. Full dashboard is now unlocked.'
                : 'Subscription refreshed. Current status: ' . strtoupper((string) $user->fresh()->subscription_status));
        } catch (\Throwable $e) {
            return redirect()->route('special-pm.billing')->with('status', 'Unable to refresh subscription: ' . $e->getMessage());
        }
    }

    public function deactivateSubscription(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'subscription_status' => 'inactive',
            'subscription_current_period_end' => null,
        ])->save();

        ActivityLog::record(
            'special_pm_subscription_deactivated',
            'Manually set Special PM subscription to INACTIVE from billing screen',
            $user
        );

        return redirect()->route('special-pm.billing')->with('status', 'Subscription status set to INACTIVE. Full dashboard access is now locked.');
    }

    public function dummyActivate(User $user): RedirectResponse
    {
        if (!app()->environment('local')) {
            abort(403, 'Dummy activation is available only in local environment.');
        }

        if ($user->role !== 'special_pm') {
            abort(403, 'Dummy activation can only be used for Special PM users.');
        }

        $user->forceFill([
            'subscription_status' => 'active',
            'subscription_current_period_end' => now()->addDays(30),
            'stripe_subscription_id' => $user->stripe_subscription_id ?: 'sub_dummy_' . $user->id,
            'stripe_price_id' => $user->stripe_price_id ?: ((string) config('services.stripe.special_pm_price_id') ?: 'price_dummy_special_pm'),
        ])->save();

        return redirect()->route('special-pm.billing')->with('status', 'Dummy QR payment activated. Full dashboard should now unlock.');
    }
}
