# Project improvement goals

Work through the eight goals in order. Mark a goal complete only after its implementation and relevant verification are finished. Update this file as work progresses.

Authentication is outside this roadmap.

**Progress: 6 of 8 goals complete (Goals 1–4, 6 and 7). Goals 5 — Member change history and 8 — Backups and recovery remain pending.**

## 1. Consistent registration validation

- [x] Validate phone numbers consistently in registration and editing.
- [x] Validate field lengths and allowed answers in both registration forms.
- [x] Ensure the LGA exists, the ward belongs to that LGA, and the polling unit belongs to that ward.
- [x] Handle fields correctly for members without voter cards.
- [x] Align the database phone field with the intended supported length using a migration that preserves existing data.
- [x] Resolve the `/new/submit` route that points to a commented-out controller method, after checking its callers.
- [x] Add and run registration tests covering successful submissions and invalid input.
- [x] Goal complete and verified.

## 2. Duplicate prevention

- [x] Normalize equivalent Nigerian phone formats before duplicate checks in registration and editing.
- [x] Review existing phone data for equivalent numbers; report conflicts before any merge or removal.
- [x] Apply consistent email normalization and uniqueness checks across both registration forms and editing.
- [x] Show clear duplicate-registration messages.
- [x] Test equivalent phone formats, duplicate emails, and updates to existing members.
- [x] Goal complete and verified.

## 3. Date filters and registration trends

- [x] Add Today, This week, This month, and custom date ranges.
- [x] Add a daily/monthly registration trend chart.
- [x] Apply date filters consistently to KPIs, charts, member results, and exports.
- [x] Preserve date selections through pagination and member navigation.
- [x] Verify date boundaries, empty results, and combined filters.
- [x] Goal complete and verified.

## 4. Better table controls

- [x] Add sorting for supported columns.
- [x] Add selectable page sizes.
- [x] Add column visibility controls.
- [x] Show active filter chips with individual remove buttons.
- [x] Preserve active selections through sorting and pagination.
- [x] Verify controls on desktop and mobile and confirm exports still include all matching records.
- [x] Goal complete and verified.

## 5. Member change history

- [ ] Record changed fields, previous values, new values, and time of change.
- [ ] Cover location, voter-card status, and membership identifiers.
- [ ] Display change history on the member view page.
- [ ] Verify successful changes are recorded and failed updates do not create misleading history.
- [ ] Goal complete and verified.

## 6. Bulk ID-card downloads

- [x] Allow selecting members for ID-card downloads.
- [x] Support all members matching the active filters across pagination.
- [x] Provide a ZIP of individual cards.
- [x] Provide a print-ready PDF option.
- [x] Preserve consistent card dimensions, design, and member-specific QR values.
- [x] Verify selection accuracy, empty results, and practical batch sizes.
- [x] Goal complete and verified.

## 7. Excel/CSV imports

- [x] Provide a downloadable import template.
- [x] Accept Excel and CSV files with clear format and size validation.
- [x] Preview records before saving.
- [x] Flag duplicates, invalid values, and inconsistent locations with row-specific errors.
- [x] Confirm the import before saving and show an import result summary.
- [x] Reuse registration validation and duplicate-prevention rules.
- [x] Verify invalid files, duplicate records, and successful imports.
- [x] Goal complete and verified.

## 8. Backups and recovery

- [ ] Identify required database and file backup coverage.
- [ ] Agree on storage destination, schedule, and retention before configuring backups.
- [ ] Configure scheduled backups and failure reporting.
- [ ] Verify restoration in an isolated environment without overwriting live data.
- [ ] Record recovery steps and the result of the restore check.
- [ ] Goal complete and verified.

## Completion log

- 2026-09-25 — Goal 1 complete: shared registration/editing validation, Nigerian phone rule, location hierarchy checks, safe optional-email handling, electoral-field exclusion for no-card registration, removal of unused `/new/submit`, and phone-column migration applied locally. Verified with 80 passing membership tests (379 assertions), including registration and migration regression coverage. PHP files formatted with Pint. Phone normalization and equivalent-number duplicate prevention remain in Goal 2.

- 2026-09-25 — Goal 2 complete: registration and editing normalize Nigerian phone variants to local 0-prefixed format and trim/lowercase emails before checking uniqueness. Checks include equivalent legacy contact values and exclude only the route-bound member being edited. Existing records were audited read-only; no bulk normalization, merge, or deletion was performed. Verified with 95 passing membership tests (438 assertions); PHP formatting passed.

- 2026-09-25 — Goal 3 complete: Today, Monday–Sunday week, calendar month, and inclusive custom date filters apply to KPIs, geographic breakdown, trends, table, and exports. Daily/monthly trends include zero-count gaps and PNG download; date context is retained through search, pagination, member navigation, and update redirects. Dates use the displayed application timezone (currently UTC); ranges over 366 days use monthly buckets. Verified with 59 focused tests (243 assertions), browser rendering and trend PNG download, and Pint formatting.

- 2026-09-25 — Goal 4 complete: allowlisted ascending/descending sorts via headers and controls, 10/25/50/100 rows per page, browser-persisted column visibility with reset, and removable filter chips with dependent-location clearing. Sorting/page size survive filters, search, pagination, and member navigation; exports follow the selected sort and retain all matching records and columns. Fixed duplicate trend initialization that interrupted shared scripts. Verified with 75 focused tests (303 assertions), JavaScript syntax check, desktop/mobile browser checks, saved column visibility after reload, and Pint formatting.

- 2026-09-25 — Goal 6 complete, prioritized at user request ahead of Goal 5. Added selected-member downloads with selection retained across pages, all-filtered ZIP downloads, and print-ready PDFs using approved setasign/fpdf 1.9.0. PNG cards reuse the exact single-card renderer; PDF sheets place 8 proportional cards on A4 at 85.6 mm width. Manual selections are limited to 500 to avoid form-input truncation; PDF batches to 200; all-filtered ZIP covers every matching member. Temporary generation files are cleaned up. Verified 54 focused tests (204 assertions), followed by all 12 batch tests including the added selection-limit case (54 assertions), desktop/mobile selection UI, real HTTP ZIP/PDF downloads, ZIP integrity, rendered PDF layout, JavaScript syntax, and Pint formatting.

- 2026-09-26 — Goal 7 complete: Excel (.xlsx) and UTF-8 CSV imports with blank templates, location reference download, 500-row/2 MB limits, row-specific preview errors, shared registration validation, normalized contact duplicates within files and existing data, and atomic confirmation with fresh validation. Preview data stays server-side for 20 minutes, tied to the uploading session; confirmation locks prevent repeat saves. IDs and polling-unit membership codes are generated on import. Added approved phpoffice/phpspreadsheet 5.10.0. Verified 91 focused tests (440 assertions), then all 30 import tests including workbook expansion/multiple-sheet and concurrency checks (147 assertions), desktop/mobile preview checks, and Pint formatting.

## Existing contact conflicts requiring review

The Goal 2 audit found 11 phone conflict groups affecting 22 members, and zero normalized email conflict groups. Existing records remain intact. Changes to records in a conflict group require a non-conflicting contact value; bulk cleanup is separate work requiring review of the actual members.

| Group | Member IDs |
| --- | --- |
| 1 | 619, 781 |
| 2 | 659, 1937 |
| 3 | 1031, 1750 |
| 4 | 1092, 4904 |
| 5 | 1152, 1850 |
| 6 | 1354, 2006 |
| 7 | 1655, 1851 |
| 8 | 1825, 1835 |
| 9 | 2098, 2693 |
| 10 | 2226, 4035 |
| 11 | 4937, 4946 |
