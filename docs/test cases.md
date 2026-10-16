# Test Cases

Manual test cases (the project has no automated suite). Run locally with `php -S localhost:8000`.

**Valid baseline payload** for API tests: name, company, role, email `a@b.com`, companyWebsite `example.com`, `q01`–`q17` = 3, `priorityArea` = `Risk Appetite`, `priorityArea2` = `Automation`, `outcome` = `Grow revenue`.

## 1. Survey UI

| ID | Test | Steps | Expected |
|---|---|---|---|
| TC-UI-01 | Initial load | Open `/` | Step 1 (contact info) shown; no results visible |
| TC-UI-02 | Required contact fields | Leave fields blank, click Next | Blocked with message; stays on step 1 |
| TC-UI-03 | Invalid email | Enter `abc` as email, Next | Blocked / error shown |
| TC-UI-04 | Step flow | Complete each step | Order: Contact → Culture → Strategy → Capabilities → Organization & Talent → Priorities |
| TC-UI-05 | Unrated items | Skip a rating on a dimension step, Next | Blocked; missing item indicated |
| TC-UI-06 | Back navigation | Go back from step 3 to 2 | Previously chosen ratings preserved |
| TC-UI-07 | Priority dropdown | Open dropdown on step 6 | 17 factors grouped under 4 dimensions |
| TC-UI-08 | Same priority twice | Pick same factor for both | Error; cannot submit |
| TC-UI-09 | Empty outcome | Leave outcome blank, submit | Error; no results shown |
| TC-UI-10 | Successful submit | Complete valid form, submit | Results with overall score, level, 4 dimension scores |
| TC-UI-11 | Server error | Make `data/` unwritable, submit | Error message shown; form data retained; no results |
| TC-UI-12 | Responsive | View at 360 px width | No horizontal scroll; controls usable |
| TC-UI-13 | Honeypot hidden | Inspect page | `website` field not visible/focusable by users |

## 2. Submission API (`submit.php`)

| ID | Test | Input | Expected |
|---|---|---|---|
| TC-API-01 | Valid submission | Baseline payload | 200, `ok:true`, overall 50, level `Progressing`, row appended |
| TC-API-02 | GET method | `GET /submit.php` | 405 |
| TC-API-03 | Oversized body | Body > 16000 bytes | 413 |
| TC-API-04 | Invalid JSON | `{bad` | 400 |
| TC-API-05 | JSON array/scalar | `"x"` | 400 |
| TC-API-06 | Honeypot | `website: "spam"` | 200 fake success, overall 0; no CSV row |
| TC-API-07 | Missing name | name empty | 422 |
| TC-API-08 | Invalid email | `email: "x"` | 422 |
| TC-API-09 | Invalid website | `companyWebsite: "not a site"` | 422 |
| TC-API-10 | Rating missing | omit `q10` | 422 |
| TC-API-11 | Rating out of range | `q05: 6`, `q05: 0` | 422 each |
| TC-API-12 | Rating as string | `q01: "3"` | 422 |
| TC-API-13 | Unknown priority | `priorityArea: "Foo"` | 422 |
| TC-API-14 | Duplicate priority | both `Automation` | 422 |
| TC-API-15 | Empty outcome | `outcome: ""` | 422 |
| TC-API-16 | Long text | name of 300 chars | Saved truncated to 120 chars |
| TC-API-17 | Client score ignored | add `overall: 100` | Server score computed from ratings |

## 3. Scoring

| ID | Ratings | Expected overall | Expected level |
|---|---|---|---|
| TC-SC-01 | all 1 | 0 | Exploring |
| TC-SC-02 | all 2 | 25 | Emerging |
| TC-SC-03 | all 3 | 50 | Progressing |
| TC-SC-04 | all 4 | 75 | Advancing |
| TC-SC-05 | all 5 | 100 | Advancing |
| TC-SC-06 | Culture all 5, rest 1 | Culture 100, Strategy/Capabilities/Org 0 | overall 29 (mean 37/17 = 2.176), level Emerging |
| TC-SC-07 | Boundary: mean yielding 24.5–25 | verify rounding to nearest integer | Level matches rounded value |

## 4. CSV storage

| ID | Test | Expected |
|---|---|---|
| TC-CSV-01 | First submission to empty file | Header row then one data row |
| TC-CSV-02 | Second submission | One new row, header not repeated |
| TC-CSV-03 | Column order | Matches schema in detailed requirement §1.5 |
| TC-CSV-04 | Formula injection | Name `=1+1` stored as `'=1+1` |
| TC-CSV-05 | Commas / quotes / newlines in outcome | Correctly quoted; file opens with right column count |
| TC-CSV-06 | Concurrent submissions | 10 parallel POSTs → 10 intact rows |
| TC-CSV-07 | Priority format | Stored as `Dimension: Factor` |

## 5. Email

| ID | Test | Expected |
|---|---|---|
| TC-EM-01 | SMTP configured, valid submit | Respondent receives email with PDF; response `emailed:true` |
| TC-EM-02 | SMTP host empty | No mail attempted; `emailed:false`; save succeeds |
| TC-EM-03 | Wrong SMTP credentials | Error logged; save succeeds; HTTP 200 |
| TC-EM-04 | PDF content | Contains name, company, scores, level, priorities, outcome, date |

## 6. Admin

| ID | Test | Expected |
|---|---|---|
| TC-AD-01 | Open `/admin.php` unauthenticated | Login form only |
| TC-AD-02 | Wrong password | Error; no count or download |
| TC-AD-03 | Correct password | Dashboard with submission count |
| TC-AD-04 | Download CSV | `?download=1` returns full CSV |
| TC-AD-05 | Download without session | Redirected / denied; no CSV |
| TC-AD-06 | Logout | Session ended; download denied afterwards |
| TC-AD-07 | Count accuracy | Count equals data rows in CSV (excluding header) |

## 7. Security & deployment

| ID | Test | Expected |
|---|---|---|
| TC-SEC-01 | GET `/config.php` | Forbidden / no content |
| TC-SEC-02 | GET `/README.md` | Forbidden |
| TC-SEC-03 | GET `/data/responses.csv` | Forbidden |
| TC-SEC-04 | GET `/data/` | No directory listing |
| TC-SEC-05 | XSS in name (`<script>alert(1)</script>`) | Rendered as text in results/admin/email; no script runs |
| TC-SEC-06 | Admin password not exposed | Not present in any HTTP response body |

## 8. Cross-browser

| ID | Test | Expected |
|---|---|---|
| TC-BR-01 | Chrome, Edge, Firefox, Safari | Full flow completes with correct results |
| TC-BR-02 | Mobile browser | Full flow completes; layout intact |
