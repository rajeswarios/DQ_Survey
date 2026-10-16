# Detailed Requirements

## 1. Functional requirements

### 1.1 Survey form (`index.html`)

| ID | Requirement |
|---|---|
| FR-01 | The survey is presented as 6 steps: (1) contact info, (2) Culture, (3) Strategy, (4) Capabilities, (5) Organization & Talent, (6) priorities and outcome. |
| FR-02 | Step 1 collects: name, company, company website, designation/role, email — all mandatory. |
| FR-03 | Each of the 17 statements (`q01`–`q17`) is rated on an integer scale 1–5. |
| FR-04 | The user cannot advance past a step until all required fields on it are completed; a clear message identifies missing items. |
| FR-05 | Users can navigate back to previous steps without losing entered data. |
| FR-06 | Step 6 asks for top priority and second-highest priority from a dropdown of the 17 factors grouped by dimension (`<optgroup>`), and a free-text desired business outcome. |
| FR-07 | Top and second-highest priority must be different. |
| FR-08 | A hidden honeypot field `website` exists and is never visible to users. |
| FR-09 | Results (overall score, readiness level, per-dimension scores) are displayed only after `submit.php` returns `ok: true`. |
| FR-10 | If submission fails, the user sees the server's error message and keeps their entered data. |
| FR-11 | The UI is usable on mobile and desktop viewports. |

### 1.2 Questions and dimensions

| Dimension | IDs | Factors |
|---|---|---|
| Culture | q01–q05 | Risk Appetite, Test and Learn, Speed / Agility, Collaboration, External Orientation |
| Strategy | q06–q08 | Linked to Business Strategy, Bold Long-term Orientation, Centered Around Customer Needs |
| Capabilities | q09–q14 | Connectivity, Automation, Content, Data / Analytics, Customer Experience, Technology |
| Organization & Talent | q15–q17 | Partners, Processes, People |

### 1.3 Submission endpoint (`submit.php`)

| ID | Requirement |
|---|---|
| FR-20 | Accept only `POST`; other methods return 405. |
| FR-21 | Reject bodies over 16,000 bytes with 413. |
| FR-22 | Reject malformed JSON / non-object payloads with 400. |
| FR-23 | If honeypot `website` is non-empty, return a fake success (200, overall 0) and store nothing. |
| FR-24 | Trim and truncate name, company, email, role, website to 120 chars; outcome to 1000 chars. |
| FR-25 | Require non-empty name, company, role, and a valid email; else 422. |
| FR-26 | Require a syntactically valid company website (optional `http(s)://`); else 422. |
| FR-27 | Require `q01`–`q17` to be present as integers 1–5; else 422. |
| FR-28 | Validate `priorityArea` and `priorityArea2` against the factor map; they must differ; else 422. |
| FR-29 | Require a non-empty desired outcome; else 422. |
| FR-30 | Recompute scores server-side; client-supplied scores are ignored. |
| FR-31 | Append one CSV row under exclusive `flock`; write the header only when the file is empty. |
| FR-32 | Prefix any string value starting with `= + - @` (after whitespace) with `'`. |
| FR-33 | On storage failure return 500 with a user-safe message. |
| FR-34 | Response JSON on success: `ok`, `overall`, `level`, `dimensions`, `emailed`. |

### 1.4 Scoring

| ID | Requirement |
|---|---|
| FR-40 | Dimension score = round((mean of its items − 1) × 25). |
| FR-41 | Overall score = round((mean of all 17 items − 1) × 25). |
| FR-42 | Level: Exploring (<25), Emerging (25–49), Progressing (50–74), Advancing (≥75). |

### 1.5 CSV schema

Columns in order: `submitted_at_utc, response_id, name, email, company, company_website, role, q01…q17, top_priority, second_highest_priority, desired_outcome, dq_score_100, readiness_level, culture_score, strategy_score, capabilities_score, organization_talent_score`.
Priorities are stored as `Dimension: Factor`.

### 1.6 Email summary

| ID | Requirement |
|---|---|
| FR-50 | If `smtp.host` in `config.php` is non-empty, send a PDF summary to the respondent after saving. |
| FR-51 | The email includes name, company, overall score, level, dimension scores, factor ratings, priorities, outcome, and date. |
| FR-52 | Any mail failure is logged and does not alter the successful save response. |
| FR-53 | If `smtp.host` is empty, no mail is attempted. |

### 1.7 Admin (`admin.php`)

| ID | Requirement |
|---|---|
| FR-60 | Access requires the password from `config.php`; wrong password shows an error and no data. |
| FR-61 | After login (session) show total submission count. |
| FR-62 | `?download=1` streams `responses.csv` only for authenticated sessions. |
| FR-63 | The admin can log out, ending the session. |

## 2. Non-functional requirements

| ID | Category | Requirement |
|---|---|---|
| NFR-01 | Compatibility | Runs on PHP 8.0+ and Apache/cPanel with no build step. |
| NFR-02 | Security | `config.php`, `README.md` and `data/` are not web-accessible; directory indexing disabled. |
| NFR-03 | Security | All user-provided text rendered in HTML is escaped. |
| NFR-04 | Security | The admin password is never echoed in responses or other files. |
| NFR-05 | Reliability | Concurrent submissions do not corrupt the CSV (file locking). |
| NFR-06 | Performance | A submission completes in under 3 seconds on typical shared hosting (excluding SMTP latency). |
| NFR-07 | Privacy | Only data needed for assessment is collected; PII is limited to contact fields. |
| NFR-08 | Usability | Works in current Chrome, Edge, Firefox, Safari; responsive down to 360 px width. |
| NFR-09 | Maintainability | IDs, categories and factor names are kept in sync (see CLAUDE.md). |

## 3. Business rules

1. Scoring is survey-authored and not externally validated; the README must say so.
2. All 17 items carry equal weight.
3. A response is only persisted after full server-side validation.
4. Changing questions/columns after data exists requires rotating or migrating the CSV.

## 4. Error responses

| HTTP | Condition |
|---|---|
| 400 | Invalid JSON / non-object body |
| 405 | Non-POST method |
| 413 | Body larger than 16,000 bytes |
| 422 | Validation failure (contact, website, ratings, priorities, outcome) |
| 500 | Storage unavailable or lock failure |
