# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

A small, dependency-free PHP + vanilla JS survey ("Digital Transformation Readiness Assessment", 17 DQ factors across 4 dimensions). Intended to be uploaded as-is to an Apache/cPanel PHP 8.0+ host (e.g. `public_html/dq-survey/`). There is no build step, package manager, linter, or test suite. The repo is not a git repository.

To run locally: `php -S localhost:8000` from the repo root, then open `/` (survey) and `/admin.php`. Opening `index.html` from disk renders the form but cannot save (needs `submit.php`).

## Architecture

- `index.html` — entire respondent UI (inline CSS + one `<script>`, minified-style long lines). Defines `items` (the 17 rating statements with `id` `q01`–`q17` and `category`), `groups` (the dimension steps), `responses`, and `step`. A 6-step flow: contact info → one step per dimension (4) → priorities/outcome. Results are shown only after `submit.php` returns `ok`.
- `submit.php` — JSON POST endpoint. Validates input, **recomputes scores server-side**, and appends a row to `data/responses.csv` under `flock`. Honeypot field `website` short-circuits with a fake success. Body capped at 16000 bytes.
- `admin.php` — single-file, session-based admin: password check against `config.php`, shows submission count, streams CSV via `?download=1`.
- `config.php` — returns `['admin_password' => ...]`. Contains a plaintext password; do not echo it into other files or output.
- `.htaccess` (root) denies `config.php`/`README.md` and disables indexing; `data/.htaccess` + `data/index.html` block browsing of the CSV. On non-Apache servers equivalent rules are required.

## Things that must stay in sync

- **Question IDs / categories**: `items` in `index.html` and the `$categories` map in `submit.php` (`Culture` q01–05, `Strategy` q06–08, `Capabilities` q09–14, `Organization & Talent` q15–17) must match; `submit.php` hard-requires exactly `q01`..`q17` as ints 1–5.
- **Priority options**: the `priorityArea` `<select>` in `index.html` is built from `items` (grouped by dimension via `<optgroup>`); `submit.php` validates it against the `$factors` map (q01–q17 → dimension, factor name), so factor names must match or submissions get a 422. The CSV stores it as `Dimension: Factor`.
- **Email**: after saving, `submit.php` calls `send_summary_email()` in `mailer.php` (PHPMailer in `phpmailer/`, hand-rolled PDF writer, no other dependencies). SMTP host/port/username/password are set under `smtp` in `config.php`; empty `host` disables mail. Mail failures are logged and never block the save.
- **Scoring**: score = `(mean − 1) × 25` (0–100), equal weight for all 17 items; bands Exploring <25, Emerging <50, Progressing <75, Advancing ≥75. Defined in `submit.php`; the README documents these as survey-authored, not externally validated.
- **CSV schema**: header is written only when the file is empty (`submit.php`). Changing columns/questions after responses exist makes the existing CSV inconsistent — rotate or migrate the file first. Values starting with `= + - @` are prefixed with `'` (formula-injection guard); keep that when adding fields.
- Dimension display names appear in `index.html`, `submit.php` (response JSON keys) and the CSV header (`*_score` columns).
