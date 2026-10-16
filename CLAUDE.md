# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A small, dependency-free PHP + vanilla JS survey ("Digital Transformation Readiness Assessment", 17 DQ factors across 4 dimensions). Intended to be uploaded as-is to an Apache/cPanel PHP 8.0+ host (e.g. `public_html/dq-survey/`). There is no build step, package manager, linter, or automated test suite (manual test cases live in `docs/test cases.md`).

To run locally: `php -S localhost:8000` from the repo root, then open `/` (survey) and `/admin.php`. Opening `index.html` from disk renders the form but cannot save (needs `submit.php`).

## Docs

`docs/` holds the project documentation; keep it in step with code changes:

- `docs/scope.md` — in/out of scope, assumptions, constraints.
- `docs/detailed requirement.md` — numbered requirements (FR-xx / NFR-xx), CSV schema, error codes.
- `docs/test cases.md` — manual test plan (UI, API, scoring, CSV, email, admin, security).
- `docs/skills.md` — skills needed to build/maintain/operate the site.

## Architecture

- `index.html` — entire respondent UI (inline CSS + one `<script>`, minified-style long lines). Defines `items` (the 17 rating statements with `id` `q01`–`q17`, `category`, `factor`, `question`), `groups` (the dimension steps), `responses`, and `step`. A 6-step flow: contact info (name, company, company website, designation/role, email — all required) → one step per dimension (4) → priorities/outcome. Results are shown only after `submit.php` returns `ok`.
- `submit.php` — JSON POST endpoint. Validates input (405 non-POST, 413 body >16000 bytes, 400 bad JSON, 422 validation), **recomputes scores server-side**, appends a row to `data/responses.csv` under `flock`, then attempts the summary email. Honeypot field `website` short-circuits with a fake success (nothing stored). Success JSON: `ok`, `overall`, `level`, `dimensions`, `emailed`.
- `mailer.php` — `build_summary_pdf()` (hand-rolled PDF writer) and `send_summary_email()` (PHPMailer from `phpmailer/`). Sends the PDF to the respondent and BCCs `admin_copy` if set. Blocked from web access in `.htaccess`.
- `admin.php` — single-file, session-based admin: password check against `config.php` (plaintext or `password_hash()` value both accepted), brute-force lockout (5 bad tries → 60 s), shows submission count, streams CSV via `?download=1`, logout via POST. Sends hardening headers (nosniff, X-Frame-Options DENY, no-store).
- `config.php` — returns `['admin_password' => ..., 'smtp' => [host, port, encryption, username, password, from_email, from_name, admin_copy]]`. Contains plaintext secrets (admin password, SMTP password); never echo them into other files, output, or commits. Empty `smtp.host` disables mail.
- `.htaccess` (root) denies `config.php`, `README.md` and `mailer.php`, and disables indexing; `data/.htaccess` + `data/index.html` block browsing of the CSV. On non-Apache servers equivalent rules are required.

## Things that must stay in sync

- **Question IDs / categories**: `items` in `index.html` and the `$categories` map in `submit.php` (`Culture` q01–05, `Strategy` q06–08, `Capabilities` q09–14, `Organization & Talent` q15–17) must match; `submit.php` hard-requires exactly `q01`..`q17` as ints 1–5 (strings like `"3"` are rejected).
- **Priority options**: there are two required, distinct selections — `priorityArea` (top) and `priorityArea2` (second-highest) — both custom dropdowns built from `items` (grouped by dimension). `submit.php` validates each by factor name against the `$factors` map (q01–q17 → dimension, factor name), so names must match or submissions get a 422; identical picks also get a 422. The CSV stores them as `Dimension: Factor`.
- **Required fields**: name, company, role, valid email, valid `companyWebsite` (optional `http(s)://`), both priorities, and a non-empty `outcome` (≤1000 chars). Text fields are trimmed and truncated to 120 chars. Validation exists in both `index.html` (`valid()`) and `submit.php`; change both.
- **Email**: after saving, `submit.php` calls `send_summary_email()` in `mailer.php`. Mail failures are logged via `error_log` and never block the save (`emailed` is just false).
- **Scoring**: score = `(mean − 1) × 25` (0–100, rounded), equal weight for all 17 items, computed per dimension and overall; bands Exploring <25, Emerging <50, Progressing <75, Advancing ≥75. Defined in `submit.php`; the README documents these as survey-authored, not externally validated.
- **CSV schema**: header is written only when the file is empty (`submit.php`). Columns: `submitted_at_utc, response_id, name, email, company, company_website, role, q01…q17, top_priority, second_highest_priority, desired_outcome, dq_score_100, readiness_level, culture_score, strategy_score, capabilities_score, organization_talent_score`. Changing columns/questions after responses exist makes the existing CSV inconsistent — rotate or migrate the file first, and update `docs/detailed requirement.md` §1.5. Values starting with `= + - @` are prefixed with `'` (formula-injection guard); keep that when adding fields.
- Dimension display names appear in `index.html`, `submit.php` (response JSON keys) and the CSV header (`*_score` columns).
