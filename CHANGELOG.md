# Changelog

## 3.3.0 — 2026-10-19

- Centralize active portfolio/current application queries across reports and dashboard.
- Extract transactional financial-statement writes with row locking, immutable project ownership, mandatory audit and duplicate-write validation.
- Select latest modern financial periods deterministically; flag ambiguous company records and preserve missing profit as unknown.
- Preserve overpayment as negative remaining commitments in report totals and rows.
- Constructor-inject report dependencies and share latest-statement selection.
- Add regression tests and SQLite/PHP 8.2–8.3 and MySQL 8 CI jobs.
- Document architecture decisions, remaining boundaries and deployment verification.
- No new schema migration or dependency version upgrade.

## 3.2.5 — 2026-10-19

- Replace outdated financial-management test schema with real migrations.
- Update period-label assertion and verify deletion audit history.
- Guard destructive non-SQLite tests against unintended database names.

Earlier history is available in git; previous application commit label was v3.2.4.
