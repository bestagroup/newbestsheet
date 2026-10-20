# Changelog

## 3.5.0 — 2026-10-20

- Introduce draft, submitted and approved financial review states, preserving legacy records as explicitly unreviewed.
- Separate review authorization from editing; prevent every known preparer and submitter from reviewing their own revision, including superadmins.
- Store immutable approved snapshots and audited correction revisions; report the last approved version while corrections are pending.
- Validate required financial totals, exact balance equality and period/ownership integrity at submission and approval.
- Add optimistic workflow concurrency checks, mandatory return/correction reasons and review history UI.
- Apply reporting versions consistently to board charts, totals, portfolio exports and financial dashboard items.
- Add resumable migration and SQLite/MySQL workflow, authorization, snapshot and rollback tests.

## 3.4.0 — 2026-10-19

- Add resumable, non-destructive company ownership and explicit Solar Hijri period metadata migration.
- Validate standalone and YTD periods without inferring legacy dates; preserve exact monetary amounts.
- Serialize company-period writes and reject duplicates across dossiers and ownership reassignment.
- Separate board chart bases and exact date ranges, preserving authorized source-project visibility.
- Provide read-only pre/post-upgrade financial quality audit and operational remediation guide.
- Extend SQLite and MySQL regression coverage for data preservation and interrupted migration recovery.

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
