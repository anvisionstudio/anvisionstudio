# an_Vision Project OS — Architecture

WordPress plugin (`anvision-project-os`) that backs the Project OS admin console. Code prefix: `avs_`. Namespace: `AnvisionStudio\ProjectOS`.

## Layering

| Layer | Responsibility | May depend on |
| --- | --- | --- |
| **Domain** | Entities, value objects, state machines, invariants (money, idempotency rules) | Nothing outside Domain |
| **Application** | Use cases, orchestration, DTOs | Domain |
| **Infrastructure** | REST, `$wpdb`, migrations, WordPress hooks | Application, Domain |

Dependency rule: Infrastructure → Application → Domain only.

## Money

- All amounts stored as **integer minor units** (e.g. TWD cents) with ISO 4217 currency code.
- **Server recalculates** subtotal, tax, and total from line items on every write; client-supplied totals are ignored.
- Line item: `{ description, quantity, unit_price_minor, tax_rate_bps }` (`tax_rate_bps` = basis points).

## Idempotency-Key

Write routes accept optional header `Idempotency-Key` (max 128 chars).

1. Canonical request fingerprint = SHA-256 of `METHOD + route + normalized JSON body`.
2. **New key** → execute, persist fingerprint + HTTP status + JSON response (24h TTL).
3. **Same key, same fingerprint** → replay stored response (including status).
4. **Same key, different fingerprint** → **409 Conflict** (`avs_idempotency_key_mismatch`).

## Database migrations

- Table `{prefix}avs_schema_migrations` records applied migration IDs (lexicographic version strings).
- Each migration class implements `up()` only; versions are immutable once shipped.
- Migrations run on plugin activation and before REST boot if pending.

## Quotations

- **Quotation** — logical document; has `review_status` and pointer to latest version.
- **Quotation version** — mutable working revision (`is_snapshot = 0`).
- **Snapshot** — immutable copy of a version (`is_snapshot = 1`); no updates or deletes.

Creating a version always recalculates money. Snapshots duplicate line items and totals at creation time.

## Review state machine

States: `draft` → `in_review` → `approved` | `rejected`; `rejected` → `draft` (revise).

| Transition | From | To |
| --- | --- | --- |
| submit | draft, rejected | in_review |
| approve | in_review | approved |
| reject | in_review | rejected |
| reopen | approved | in_review |

Invalid transitions return **422**. Every transition writes an **audit log** row (from, to, actor, optional note).

## REST API (`avs/v1`)

- `POST /quotations` — create quotation + v1
- `GET /quotations/{id}`
- `POST /quotations/{id}/versions` — new version from payload line items
- `POST /quotations/{id}/versions/{version_id}/snapshot`
- `POST /quotations/{id}/review` — body `{ "action": "submit"|"approve"|"reject"|"reopen", "note": "..." }`

All write routes honor Idempotency-Key. Capability: `manage_avs_projects`.

## Testing

- **Unit** — domain calculators and state machine (no WordPress).
- **Integration** — `WP_UnitTestCase` against WordPress test library + MySQL (CI).
