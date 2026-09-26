# Changelog v1.0.16

## Fixes

- Product detail always exposes the configured source-currency symbol beside the active rate, even when a variant uses an IDR pricelist.
- Product create/update requests that omit the newer `tax_status` field now safely default to `excluded`; explicit values are still validated against the allowed enum.
- Restores the expected `close_at` validation path for legacy requests/tests creating a PO without a Close PO date.
