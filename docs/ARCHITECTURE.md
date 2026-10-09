# an_Vision Project OS — v0.1 development baseline

## Scope
Phase 1 plugin scaffold: read-only Service Catalog, draft quotation creation and retrieval, server-authoritative integer-TWD pricing, versionable DB migration at activation, admin-only API access. NOT production ready.

## Source of truth
- Project OS WordPress MySQL: quotes, accepted contractual scope, amounts, cost, payments, access grants.
- Notion (future connector): collaborative tasks, meeting notes, SOPs.
- Drive (future connector): original PDFs and signed evidence.
- AI (future): proposals only; human approval required for financial and contractual mutations.

## Engineering rules
- API-first; response `{success,code,message,data,meta}`.
- Domain pure PHP: no WordPress calls.
- Infrastructure handles `$wpdb`, WordPress hooks and adapters.
- Every route has `permission_callback` and checks resource ownership before customer access.
- Quote totals always calculated server-side; quoted catalog price is a snapshot (not supplied totals).
- Never expose cost rate or draft quotes in client/partner endpoints.
- Transactions for multi-table writes; idempotency on monetary/business operations.
- Migrator/versioning, update signature verification, outbox, audit, schema validation and portal role separation required BEFORE production.

## Explicit exclusions of this scaffold
Quotation version snapshots, immutable approval, PDF output, clients/CRM, contracts, payments, invoicing, Notion sync, queue, audit logger, external portals, UI, e-invoicing. These are planned, NOT implemented.

## Review gates
1. Cursor implements feature on branch.
2. Claude independently audits security and tests with `review/CLAUDE_REVIEW.md`.
3. Codex independently audits with `review/CODEX_REVIEW.md`.
4. ChatGPT checks invariants, boundaries, acceptance tests, and merges findings into architecture decisions.
5. No production deployment before all critical/high findings resolved.
