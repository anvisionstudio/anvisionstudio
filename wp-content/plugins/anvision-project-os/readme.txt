=== an_Vision Project OS ===
Contributors: anvisionstudio
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 0.2.0
License: GPLv2 or later

Phase 1 scaffold: read-only Service Catalog, draft quotations, integer-TWD pricing. NOT production ready.

== Description ==

See docs/ARCHITECTURE.md for the authoritative v0.1 baseline.

In scope:
* Domain / Application / Infrastructure layering (Domain is pure PHP)
* Read-only Service Catalog (`GET /avs/v1/services`)
* Draft quotation create/get with server-calculated integer TWD totals
* Versioned migrations on activation
* Idempotency-Key on monetary creates (409 on body mismatch)
* API envelope `{success,code,message,data,meta}`
* Admin-only (`manage_avs_projects`)

Explicitly out of scope for this scaffold: quotation version snapshots, review/approval FSM, audit logger, PDF, CRM, payments, Notion, portals, UI.
