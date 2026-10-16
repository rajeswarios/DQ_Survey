# Digital Transformation Readiness Assessment – deployable website package

## Based on the attached PPT
The source presentation has one DQ overview slide. Its four sections and all 17 named factors are preserved as survey dimensions. The assessment statements, 1–5 rating scale, scoring, and priority questions were developed for this survey; they were not present verbatim in the PPT.

## Install on a PHP website (Apache/cPanel)
1. Upload **all files and the data folder**, preserving the folder structure, to e.g. `public_html/dq-survey/`.
2. Ensure your server supports PHP 8.0+ (mbstring is recommended), and that PHP can write into `data/`. Use standard restricted directory permissions; avoid 777.
3. Browse `https://yourdomain.com/dq-survey/` and submit one test response.
4. View results at `https://yourdomain.com/dq-survey/admin.php`.
5. Copy `config.sample.php` to `config.php` and set your own strong admin password and SMTP details **before distributing** the survey link. `config.php` is git-ignored; never commit it.
6. Verify the `data/` folder is not browsable (HTTP 403 for `/dq-survey/data/responses.csv`). This package includes an Apache `.htaccess` rule for that. **If running nginx or another web server, configure equivalent access blocks before going live.**
7. Use HTTPS; obtain any required client privacy/consent approvals. Responses contain contact information and should be retained/deleted per your organization's policy.

## Features
- Responsive questionnaire: 17 ratings under Culture (5), Strategy (3), Capabilities (6), Organization & Talent (3).
- Contact information and digital priority and business outcome questions.
- Completion checks, 6 steps and progress indicator.
- Computes dimension / overall score 0–100, where 1→0 and 5→100. All 17 items are equally weighted in the overall score.
- Shows an individual result only **after** the server saves the response.
- Submissions appended to `data/responses.csv`; admin screen can download Excel-compatible CSV.
- Interpretation bands (Exploring 0–24; Emerging 25–49; Progressing 50–74; Advancing 75–100) are indicative categories created for this survey, **not validated external benchmarks**.

## Operating notes
- PHP host required for live collection; opening `index.html` directly from a local disk will not save responses.
- No external libraries, CDN, or third-party submission services are required.
- Respondent view is deliberately one page per dimension with internal step navigation, not one browser page per question.
- For high-volume public deployment, add server-side rate limits, CSRF defenses, spam protection and a database-driven backend. This lightweight version is suitable for a controlled client session.
- Back up the CSV securely; avoid publishing or emailing the password.
- Do not claim the questions or scoring were supplied by the original PPT; only the taxonomy of DQ factors came from the slide.

## Email summary (PDF)

Each respondent is emailed a PDF summary via PHPMailer (bundled in `phpmailer/`). Set your SMTP details in `config.php` under `smtp`: `host`, `port`, `encryption` (`tls`/`ssl`), `username`, `password`, `from_email`, optional `admin_copy`. Leave `host` empty to disable emailing. Failures are written to the PHP error log and never block the submission.
