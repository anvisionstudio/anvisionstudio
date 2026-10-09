=== an_Vision Project OS ===
Contributors: anvisionstudio
Requires at least: 6.6
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 0.2.0
License: GPLv2 or later

Backend for the an_Vision Project OS admin console: quotations, review workflow, and idempotent REST API.

== Description ==

Layered Domain / Application / Infrastructure plugin providing:

* Server-side money recalculation
* Idempotency-Key with 409 on body mismatch
* Versioned database migrations
* Quotation versions and immutable snapshots
* Review state machine with audit log

REST namespace: `avs/v1`. Capability: `manage_avs_projects`.
