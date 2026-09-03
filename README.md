# Aspen Training Entitlements

Production-oriented WordPress/WooCommerce plugin for selling shareable batches of LifterLMS course enrollments.

## Installation and requirements

1. Upload the repository ZIP from **Plugins → Add Plugin → Upload Plugin**, or extract it into `wp-content/plugins/`.
2. Activate **Aspen Training Entitlements**. Activation creates the tables and rewrite rules.
3. Ensure WordPress 6.4+, PHP 7.4+, WooCommerce, and LifterLMS are active.

WooCommerce Subscriptions is optional and detected through its public helper functions. Memberships, FluentCRM, and Fluent Forms are not dependencies. Deactivation preserves all data; there is deliberately no destructive uninstall routine.

## Product configuration

Edit a product and open **Training Entitlements**. Enable it, choose a published LifterLMS course, set positive **Entitlements Per Unit**, and set the redemption window (30 days by default). Purchased WooCommerce quantity multiplies the seat count. Variable and variable-subscription variations can override course, seats, or days; blank overrides inherit their parent.

## Provisioning and subscriptions

Batches are provisioned only from paid orders through WooCommerce payment-complete/paid-status hooks. A unique database key on the order item makes repeated hooks idempotent. Initial subscription payments and every successful renewal order therefore create independent batches. Failed renewals create nothing; a later successful payment creates exactly one batch. Seats never roll over. A full refund revokes associated batches without unenrolling trainees or deleting history.

## Redemption and expiration

Customers find batches at **My Account → Training Enrollments**, copy the shared URL, and distribute it. An anonymous visitor sees only course/expiration information and is returned after WordPress login or registration. A logged-in visitor explicitly submits a nonce-protected redemption. Capacity is reserved by a conditional atomic SQL update, then direct `llms_enroll_student()` enrollment is attempted. A failed enrollment releases the reservation. Completed identity values are snapshots and do not change with later profile edits.

Expiration is stored in UTC after calculation in the WordPress site timezone and is always enforced at redemption time. Expired and revoked batches and their rosters remain available historically.

## Administration

Administrators with `manage_woocommerce` use **WooCommerce → Training Entitlements** to filter and inspect batches. Detail screens show purchaser/order/subscription/product/course, history, and audit events. Nonce-protected actions revoke/reactivate, regenerate a link, change expiration, and adjust capacity (never below used seats). Regeneration immediately invalidates the old token.

## Database tables

Tables use the current WordPress prefix:

* `training_entitlement_batches`: source, purchaser, course, counters, state, expiration, and an HMAC token hash. `order_item_id` and `token_hash` are unique.
* `training_entitlement_redemptions`: pending/completed/failed attempts and immutable completed identity snapshots. `(batch_id,user_id)` is unique.
* `training_entitlement_audit`: creation and administrator change history.

The schema version is stored in `ate_schema_version` and `dbDelta()` runs only on activation or a version change.

## Public actions

* `ate_batch_created( int $batch_id, int $order_id, int $course_id )`
* `ate_redemption_completed( int $batch_id, int $user_id, int $course_id, int $order_id )`

These permit optional CRM integrations; FluentCRM tags are never required for accounting or enrollment.

## Security and privacy

Tokens are 256-bit random URL-safe values; only an HMAC is stored in the batch table. The recoverable raw token is kept as protected WooCommerce order-item metadata so its purchaser can retrieve it. Private rosters require the recorded purchaser account or `manage_woocommerce`. Frontend output omits purchaser, order, and trainee details. State changes use nonces and server-side authorization.

## Known MVP limitations

* Shared batch links only; individual addressed invitations and email are intentionally absent.
* Full refunds revoke capacity. Partial refunds are intentionally conservative and do not calculate proportional seats.
* No automatic unenrollment, rollover, export, completion reporting, or purchaser seat adjustment.
* Account registration availability and the registration form are controlled by WordPress/WooCommerce settings.
