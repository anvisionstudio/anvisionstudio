# ChatGPT architecture review notes (v0.1 baseline)

Aligned to authoritative `docs/ARCHITECTURE.md` (user upload).

## In scope for this scaffold

- Domain pure PHP; Infrastructure owns `$wpdb` and WordPress hooks.
- Read-only Service Catalog.
- Draft quotation create/retrieve with server-authoritative integer-TWD totals.
- Versioned DB migrator on activation.
- Admin-only REST (`manage_avs_projects`) with `{success,code,message,data,meta}` envelope.
- Idempotency on monetary create; transactions for multi-table writes.

## Explicitly out of scope (do not implement yet)

Quotation version snapshots, immutable approval / review FSM, audit logger, PDF, clients/CRM, contracts, payments, invoicing, Notion sync, queue, portals, UI, e-invoicing.

## Prior reconstruction note

An earlier agent reconstruction treated snapshots, review FSM, and audit log as in-scope. Those conflict with the authoritative v0.1 exclusions and must not ship as Phase 1 features.
