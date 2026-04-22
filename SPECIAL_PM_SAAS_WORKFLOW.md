# Special PM White-Label SaaS Workflow (Stripe Monthly Subscription)

## What This Adds
- A new role: `special_pm`
- A dedicated dashboard: `/special-pm/dashboard`
- A billing page: `/special-pm/billing`
- A white-label settings page: `/special-pm/settings`
- A DM management page: `/special-pm/manage-dm`
- Stripe Checkout subscription flow (monthly)
- Stripe webhook endpoint: `/stripe/webhook`
- White-label profile fields per Special PM user (brand name, logo, colors)

## End-to-End Workflow
1. Admin invites a user as `special_pm`.
2. User registers and logs in.
3. User is redirected to the Special PM dashboard.
4. User opens White Label Settings and configures branding.
5. User opens Manage DM and invites Digital Marketers.
6. User opens Billing and starts Stripe Checkout.
7. Stripe creates/updates customer + subscription.
8. Webhook sync updates local subscription status in `users` table.
9. Dashboard reflects active/inactive subscription state.

## Sandbox Test Payment Setup (Stripe Test Mode)
1. Create Stripe test account at https://dashboard.stripe.com/register.
2. In Stripe Dashboard (test mode), create a monthly recurring product + price.
3. Copy these values into `.env`:
   - `STRIPE_KEY=pk_test_...`
   - `STRIPE_SECRET=sk_test_...`
   - `STRIPE_SPECIAL_PM_PRICE_ID=price_...`
   - `STRIPE_WEBHOOK_SECRET=whsec_...` (after running Stripe CLI listen command)
4. Expose your local app URL for Stripe callbacks:
   - For local Sail app, ensure `APP_URL` is reachable (for example via ngrok) or use Stripe CLI forwarding.
5. Start webhook forwarding with Stripe CLI:
   - `stripe listen --forward-to http://localhost/stripe/webhook`
   - Copy printed `whsec_...` value to `STRIPE_WEBHOOK_SECRET`.
6. In app, go to `Special PM > Billing`, click "Start / Manage Monthly Subscription".
7. Use Stripe test card in Checkout:
   - Card: `4242 4242 4242 4242`
   - Any future expiry, any CVC, any ZIP.
8. Confirm payment. You should return to billing page with active status.

## Useful Stripe Test Cards
- Success: `4242 4242 4242 4242`
- Requires authentication (3DS): `4000 0025 0000 3155`
- Card declined: `4000 0000 0000 9995`

## Operational Notes
- Billing state stored on `users` table:
  - `stripe_customer_id`
  - `stripe_subscription_id`
  - `stripe_price_id`
  - `subscription_status`
  - `subscription_current_period_end`
- Webhook events handled:
  - `checkout.session.completed`
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`

## How To Use In Production
1. Switch Stripe keys from test to live.
2. Create live product/price and set live `STRIPE_SPECIAL_PM_PRICE_ID`.
3. Configure real webhook endpoint in Stripe Dashboard:
   - `https://your-domain.com/stripe/webhook`
4. Set `STRIPE_WEBHOOK_SECRET` from live endpoint.
5. Ensure HTTPS and queue/mail reliability for production.
