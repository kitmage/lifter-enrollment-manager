-- Manual integration assertion, run against a disposable WordPress test database.
-- Two concurrent sessions execute the UPDATE used by BatchRepository::reserve().
UPDATE wp_training_entitlement_batches
SET entitlements_used = entitlements_used + 1
WHERE id = 1 AND status = 'active'
  AND entitlements_used < entitlements_total;
-- Across both sessions, ROW_COUNT() must total 1 when total=1 and used=0.
