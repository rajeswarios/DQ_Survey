# Skills

Skills required to build, maintain, and operate the Digital Transformation DQ Survey Website.

## Technical skills

| Area | Skill | Used for |
|---|---|---|
| Backend | PHP 8.0+ (strict types, `flock`, `fputcsv`, sessions, `json_decode`) | `submit.php`, `admin.php`, `mailer.php`, `config.php` |
| Frontend | HTML5, CSS3 (responsive layout, inline styles) | `index.html` UI, stepper, results view |
| Frontend | Vanilla JavaScript (DOM, `fetch`, form validation, state handling) | Multi-step flow, rating inputs, submission, results rendering |
| Data | CSV format, RFC 4180 quoting, formula-injection mitigation | `data/responses.csv`, admin download |
| Email | SMTP configuration, PHPMailer, MIME attachments | Summary email with PDF |
| Documents | Basic PDF generation (hand-rolled writer) | PDF summary attached to email |
| Hosting | Apache / cPanel, `.htaccess` rules, file permissions | Deployment to `public_html/dq-survey/` |
| Security | Input validation, honeypot spam control, session auth, output escaping, directory protection | Protecting responses and admin area |

## Domain skills

| Skill | Used for |
|---|---|
| Digital Quotient (DQ) / digital transformation readiness concepts | Defining the 17 factors across Culture, Strategy, Capabilities, Organization & Talent |
| Survey design (Likert 1–5 scales, question wording, bias) | Writing and maintaining rating statements |
| Scoring & banding | `(mean − 1) × 25` score; Exploring / Emerging / Progressing / Advancing bands |
| UX writing | Clear respondent guidance, error messages, results explanations |

## Process skills

| Skill | Used for |
|---|---|
| Requirements analysis | Maintaining `docs/detailed requirement.md` |
| Manual & exploratory testing | Executing `docs/test cases.md` (no automated suite exists) |
| Release / deployment | Uploading files, setting config, verifying `data/` is not browsable |
| Data stewardship & privacy | Handling respondent PII (name, email, company) and rotating the CSV |

## Tooling

- PHP CLI built-in server: `php -S localhost:8000`
- Any modern browser with dev tools (desktop + mobile emulation)
- Text editor, FTP/cPanel File Manager
- Spreadsheet app (Excel / Google Sheets) for reviewing CSV exports
