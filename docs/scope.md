# Scope

## Purpose

Provide a lightweight, dependency-free web survey that lets organisations self-assess their digital transformation readiness across 17 DQ factors in 4 dimensions, returns an instant score and readiness level, and collects responses for the administrator.

## In scope

1. **Respondent survey** (`index.html`)
   - 6-step flow: contact info → Culture → Strategy → Capabilities → Organization & Talent → priorities & desired outcome.
   - 17 rating statements (1–5 scale).
   - Selection of top and second-highest priority factor.
   - Free-text desired business outcome.
   - Results view (overall score, readiness level, dimension scores) shown only after successful save.
2. **Submission endpoint** (`submit.php`)
   - Server-side validation, server-side score recomputation, honeypot spam check, 16,000-byte body cap.
   - Append to `data/responses.csv` under file lock, with CSV formula-injection guard.
3. **Email summary** (`mailer.php`, `phpmailer/`)
   - PDF summary emailed to respondent when SMTP is configured; failures never block the save.
4. **Admin area** (`admin.php`)
   - Password login (from `config.php`), submission count, CSV download.
5. **Deployment & protection**
   - Apache `.htaccess` rules protecting `config.php`, `README.md`, and `data/`.
6. **Documentation** (`README.md`, `docs/`).

## Scoring model (in scope)

- Score = `(mean − 1) × 25`, range 0–100, equal weight for all 17 items.
- Bands: Exploring < 25, Emerging < 50, Progressing < 75, Advancing ≥ 75.
- Dimensions: Culture (q01–05), Strategy (q06–08), Capabilities (q09–14), Organization & Talent (q15–17).

## Out of scope

- Database storage, user accounts for respondents, or editing a submitted response.
- Multi-language / localisation.
- Benchmarking against other organisations or external validation of the model.
- Admin features beyond count and CSV download (filtering, charts, deleting rows, multiple admins).
- Automated test suite, CI/CD, build tooling.
- Non-Apache server rule sets (equivalent rules must be supplied by the deployer).
- Payment, CRM, or analytics integrations.

## Assumptions

- Hosted on Apache/cPanel with PHP 8.0+ and a writable `data/` directory.
- Single administrator sharing one password.
- Response volume is small enough for a flat CSV file.

## Constraints

- No external runtime dependencies other than the bundled PHPMailer.
- Question IDs, factor names and dimension names must stay in sync between `index.html` and `submit.php`.
- Changing CSV columns after responses exist requires rotating/migrating the file.

## Deliverables

- Deployable site files, `README.md`, and `docs/` (skills, scope, detailed requirement, test cases).
