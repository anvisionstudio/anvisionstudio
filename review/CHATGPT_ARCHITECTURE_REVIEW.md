# Architecture review notes (ChatGPT)

Reviewed against product constraints for an_Vision Project OS.

## Accepted

- Strict Domain / Application / Infrastructure split with server-side money recalculation.
- Idempotency-Key conflict on body mismatch (409) vs safe replay on match.
- Versioned SQL migrations with applied-version ledger.
- Immutable quotation snapshots separate from editable versions.
- Explicit review FSM plus append-only audit log.

## Follow-ups (not in this slice)

- Admin UI screens and block editor integration.
- Multi-currency FX and rounding policy per locale.
- Object cache layer for idempotency hot keys (Redis).
- Export / PDF generation from snapshots.
