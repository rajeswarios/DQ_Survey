# E2E Test Report

- **Date:** 2026-10-16
- **Build under test:** branch `claude/e2e-test-report-bx0d3r` (PHP 8.3.6 built-in server, Chromium via Playwright 1.56)
- **Method:** a copy of the repo was served locally with `smtp.host` blanked, so no real mail was sent and the real `data/` was never touched. Browser flows were driven with Playwright; API, CSV, admin and security checks used HTTP calls. Cases follow `docs/test cases.md`.

## Summary

| Area | Run | Result |
|---|---|---|
| Survey UI | 13 | 13 pass (plus a console 404, BUG-3) |
| Submission API | 17 + 7 extra edge cases | all pass (BUG-2 found on the 413 check via chunked bodies) |
| Scoring | 7 + rounding sweep (0–17 items at 2) | all pass |
| CSV | 7 | all pass |
| Email | 4 | 3 pass, EM-01 not run (needs live SMTP) |
| Admin | 7 + lockout | all pass, but lockout is bypassable (BUG-1) |
| Security / deployment | 6 | SEC-05/06 pass; SEC-01..04 not verifiable locally |

**3 bugs found, none critical. No code was changed.**

## Bugs

| ID | Severity | Where | Description | Proposed fix |
|---|---|---|---|---|
| BUG-1 | Medium (security) | `admin.php` | Brute-force lockout is stored in the **session**. A client that does not send the cookie gets a fresh session on every request. Reproduced: 12 wrong passwords with no cookie jar were never locked ("Too many attempts" appeared 0 times), then the correct password logged in. Only the 0.4 s delay slows an attacker. | Track failures per client IP in a small file under `data/` (or similar) and lock for 60 s after 5 failures, regardless of cookie. |
| BUG-2 | Low | `submit.php` | The 16000-byte limit (413) only checks the `Content-Length` header. A chunked request (`Transfer-Encoding: chunked`, no length) with a ~30 KB body skipped it and reached validation (422 instead of 413). Stored data stays bounded by field truncation, so impact is small. | Also check the length of the body actually read (read at most 16001 bytes). |
| BUG-3 | Low (cosmetic) | `index.html` | No favicon: browsers request `/favicon.ico`, get 404, and log a console error on every page load. | Add `<link rel="icon" href="logo.png">`. |

### Observations (not bugs)

- The progress bar reads 83% on the last step and is never 100% (the results screen hides it).
- No de-duplication: the same person can submit repeatedly and each submission is a new CSV row.
- A valid email longer than 120 characters would be truncated before validation, so it would be rejected or altered. Unlikely in practice.
- An outcome over 1000 characters is silently truncated server-side (the client also caps with `maxlength`).

## Detailed results

### 1. Survey UI (Chromium, 1280 px and 360 px)

| ID | Result | Notes |
|---|---|---|
| UI-01 Initial load | PASS | Step 1 of 6, results hidden |
| UI-02 Required fields | PASS | Blocked with message |
| UI-03 Invalid email / website | PASS | Both blocked with specific messages |
| UI-04 Step flow | PASS | Contact → Culture → Strategy → Capabilities → Organization & Talent → Priorities |
| UI-05 Unrated items | PASS | "Please rate all statements. Missing: Risk Appetite." |
| UI-06 Back navigation | PASS | Ratings preserved |
| UI-07 Priority dropdown | PASS | 17 options in 4 groups; mouse and keyboard selection work |
| UI-08 Same priority twice | PASS | Blocked client-side |
| UI-09 Empty outcome | PASS | Blocked, no results |
| UI-10 Successful submit | PASS | Overall 66 / Progressing; dimensions 75/100/50/50 match the hand calculation (62/17 mean) |
| UI-11 Server error | PASS (simulated) | With `data/` missing the API returns 500 "Storage is unavailable" and the UI shows the message and keeps the form. A permission-based test was not possible because the session runs as root. |
| UI-12 Responsive 360 px | PASS | No horizontal scroll on steps 1 and 2 |
| UI-13 Honeypot hidden | PASS | `website` field is 0×0, absolutely positioned, tabindex -1 |
| XSS (`<script>` in name, `<b>` in outcome) | PASS | Rendered as text, no dialog |
| Console errors | 1 | favicon 404 only (BUG-3) |

### 2. Submission API

TC-API-01…17 all passed: 405, 413, 400, 400, honeypot 200 with overall 0 and no row, 422 for each invalid field, 300-char name truncated to 120, client-sent `overall` ignored. Extra edge cases passed: float rating (`3.0`) → 422, boolean → 422, array as name → 422, `javascript:` website → 422, case-different priority → 422, https website with path accepted.

### 3. Scoring

- All 1s → 0 Exploring
- All 2s → 25 Emerging
- All 3s → 50 Progressing
- All 4s → 75 Advancing
- All 5s → 100 Advancing
- Culture 5 / rest 1 → overall 29 Emerging, dimensions 100/0/0/0

A rounding sweep of 18 mixes matched `round(k/17*25)` in every case.

### 4. CSV

- Header is written once.
- Every row, including quotes, commas and newlines in the outcome, has 33 columns matching the header.
- `=1+1`, `+SUM(A1)`, `@x` and ` -2+3` were stored with a leading `'`.
- Priorities are stored as `Dimension: Factor`.
- 10 parallel POSTs produced 10 intact rows with unique `response_id`s.

### 5. Email

| ID | Result | Notes |
|---|---|---|
| EM-01 SMTP valid | NOT RUN | Needs live SMTP; deliberately disabled to avoid sending real mail with the stored credentials |
| EM-02 Host empty | PASS | `emailed:false`, response saved |
| EM-03 SMTP unreachable | PASS | Connection refused → `emailed:false`, HTTP 200, row saved |
| EM-04 PDF content | PASS | `build_summary_pdf()` produced a valid 2-page PDF with respondent details, scores, level and factor ratings. Special characters (`<b>`, `'`, `\`, `é`) rendered correctly. |

### 6. Admin

| ID | Result |
|---|---|
| AD-01 Unauthenticated shows login only | PASS |
| AD-02 Wrong password | PASS |
| AD-03 Correct password → dashboard | PASS |
| AD-04 CSV download | PASS (identical to `data/responses.csv`) |
| AD-05 Download without session | PASS (login page, no CSV) |
| AD-06 Logout | PASS (download denied afterwards) |
| AD-07 Count accuracy | PASS (count equals parsed records, including multi-line outcomes) |
| Lockout after 5 bad tries (same session) | PASS, but bypassable (BUG-1) |
| Security headers | PASS (nosniff, X-Frame-Options DENY, no-store) |

### 7. Security / deployment

| ID | Result | Notes |
|---|---|---|
| SEC-01..04 | NOT VERIFIABLE | PHP's built-in server ignores `.htaccess`, so `/config.php`, `/README.md`, `/mailer.php`, `/data/responses.csv` and `/data/` returned 200. The `.htaccess` rules were reviewed by reading and look correct, but must be re-tested on the real Apache host. |
| SEC-05 XSS | PASS | Results page escaped; script payload stored as text |
| SEC-06 Admin password not exposed | PASS | Not present in `/`, `/admin.php` or `/submit.php` responses |

## Not covered

- Browsers other than Chromium.
- Real SMTP delivery and the PDF attachment in a mail client.
- `.htaccess` enforcement on real Apache.
- Load beyond 10 parallel submissions.
