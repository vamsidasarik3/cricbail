# CricBail SEO Maintenance & Operations Guide

This guide details the maintenance procedures, automated audit commands, regression testing, and Google Search Console operations for **CricBail** (`https://www.cricbail.com`).

---

## 1. Overview of the SEO Architecture

CricBail features a dynamic, server-rendered SEO architecture engineered for cricket journalism, match coverage, and interactive trivia:

- **Centralized Service:** `App\Services\SeoService` provides consistent formatting for title tags (preventing duplicate brand suffixes), meta descriptions, canonical URLs, and Open Graph cards.
- **Canonical Domain Enforcement:** Configured strictly via `config('seo.canonical_domain')` (`https://www.cricbail.com`) to eliminate local dev leaks (`localhost`, `:8084`) from production tags.
- **Structured Data (JSON-LD):**
  - **Homepage:** `WebSite` with `SearchAction` + `NewsMediaOrganization` with brand logos and social links.
  - **Articles:** `NewsArticle` with truthful `datePublished`, `dateModified`, `headline`, `author`, `publisher`, and `image` metadata, alongside `BreadcrumbList`.
  - **Quizzes:** Schema.org `Quiz` with educational question metadata and `BreadcrumbList`.
- **Dynamic XML Sitemaps:**
  - `/sitemap.xml`: Complete inventory of all canonical public pages (Homepage, Categories, Articles, Quizzes), updated dynamically upon publication/unpublication.
  - `/news-sitemap.xml`: Dedicated Google News sitemap containing news articles published within the preceding 48-hour rolling window.
- **Robots Directives:** `/robots.txt` disallows `/admin/` and private quiz sessions (`/quizzes/attempt/`), while linking to both sitemaps.

---

## 2. Running the Automated SEO Audit

The Artisan command `php artisan seo:audit` inspects the active database, file assets, application configuration, and dynamic sitemaps.

### Basic Human-Readable Audit
```bash
php artisan seo:audit
```
*Outputs a formatted table categorized by severity (`CRITICAL`, `WARNING`, `INFO`) with total issue counts.*

### Machine-Readable JSON Output
```bash
php artisan seo:audit --json
```
*Outputs structured JSON including audit timestamps, summary statistics, and line-item findings.*

### Save Audit Results to a File
```bash
php artisan seo:audit --json --output-file=storage/app/seo-audits/latest.json
```

### CI / Deployment Gate Mode
```bash
php artisan seo:audit --fail-on-critical
```
*Exits with status code `1` if any critical issue exists; exits with `0` if only noncritical editorial warnings or advisories remain.*

---

## 3. Resolving Common Audit Findings

| Severity | Issue Type | Cause & Resolution |
| :--- | :--- | :--- |
| **CRITICAL** | `Duplicate Article Slug` | Multiple published articles share the same slug. Navigate to Admin > Articles, edit the affected article, and provide a unique URL slug. |
| **CRITICAL** | `Missing Required Config` | `config/seo.php` is missing or `canonical_domain` is unset. Ensure `config/seo.php` contains `'canonical_domain' => 'https://www.cricbail.com'`. |
| **CRITICAL** | `Host Leak in Sitemap` | Sitemap contains `localhost` or development ports. Check that `APP_URL` and `config/seo.php` both point to `https://www.cricbail.com`. |
| **CRITICAL** | `Draft Content in Sitemap` | Draft articles were queried without the `published()` scope. Verify that `SitemapController` uses `Article::published()`. |
| **WARNING** | `Thin Content Warning` | Article body contains under 150 words. Expand match analysis or tournament report with substantive journalism (300+ words recommended). |
| **WARNING** | `Missing Excerpt` | Article has no short description. Edit article to add a compelling summary for meta descriptions and search snippets. |
| **WARNING** | `Featured Image Missing on Disk` | Image file referenced in database was deleted or moved. Re-upload image via Admin panel. |
| **WARNING** | `SVG Featured Image` | Open Graph/Twitter crawlers do not render SVG share cards. `SeoService` will fallback to the JPG card, but an article-specific JPG/WebP is recommended. |
| **WARNING** | `Unpublished / Draft Quiz` | Quiz has `draft` status or does not have exactly 10 questions. Add missing questions and publish. |

---

## 4. Running the Automated Regression Test Suite

CricBail includes an automated regression test suite (`tests/verify_seo_system.php`) that runs 78 isolated checks across 15 operational areas:

```bash
# Run the test suite using PHP
php tests/verify_seo_system.php
```

### Verified Test Areas:
1. **Canonical Host Enforcement:** Proves that local development hostnames never leak into metadata.
2. **Title Tag Normalization:** Verifies stripping of duplicate brand suffixes (`| CricBail | CricBail`).
3. **Homepage Schema:** Verifies `WebSite` and `NewsMediaOrganization` JSON-LD graphs.
4. **Article Server-Side Rendering:** Validates that raw server response includes semantic `<article>` tags and article body without JavaScript dependency.
5. **Article Schema:** Validates `NewsArticle` schema, author names, publication timestamps, and social cards.
6. **Draft & Private Isolation:** Confirms 404 responses for non-existent content and private quiz attempts.
7. **XML Sitemap Schema:** Validates `/sitemap.xml` namespace, endpoints, and absence of admin/draft routes.
8. **Google News Sitemap:** Validates `/news-sitemap.xml` namespace and 48-hour rolling window.
9. **Robots.txt Directives:** Validates crawler rules, disallow paths, and sitemap references.
10. **301 Permanent Redirects:** Asserts 301 responses for legacy URLs and query filter normalizations.
11. **Admin Auth Noindex:** Asserts `noindex, nofollow` headers on all administrative login screens.
12. **Dynamic Sitemap Reactivity:** Confirms newly published articles immediately appear, and unpublishing immediately removes them.
13. **Pagination Canonicals:** Asserts `/news?page=2` retains `?page=2`, while `/news?page=1` normalizes to root `/news`.
14. **Content Import Safety:** Asserts preservation of `meta_title`, `meta_description`, `source_name`, and `source_url`.

*Note: All tests use isolated in-memory or transient test records that are cleaned up in `finally` blocks. The test suite never truncates or alters production database records.*

---

## 5. Verifying Metadata and Structured Data

### Validating Structured Data
1. Open [Google Rich Results Test](https://search.google.com/test/rich-results).
2. Enter the target live URL (e.g., `https://www.cricbail.com/news/sample-slug`).
3. Verify that the following structured data items are detected without errors:
   - **Articles:** `NewsArticle` (Green checkmark, Valid)
   - **Quizzes:** `Quiz` (Valid)
   - **Breadcrumbs:** `BreadcrumbList` (Valid)
4. Alternately, use the [Schema Markup Validator](https://validator.schema.org/) to inspect the raw JSON-LD graph.

### Inspecting Server-Side HTML
Search engine crawlers index raw HTML before running JavaScript:
```bash
# Verify raw HTML output from terminal
curl -sSL https://www.cricbail.com/news/sample-slug | grep -i "article-content"
```
Ensure that the full article text and headings are present directly in the response body.

---

## 6. Validating XML Sitemaps

### Checking Sitemap Endpoints
```bash
# Validate main sitemap
curl -sSL -I https://www.cricbail.com/sitemap.xml
# Expected: HTTP 200, Content-Type: text/xml or application/xml

# Validate Google News sitemap
curl -sSL -I https://www.cricbail.com/news-sitemap.xml
# Expected: HTTP 200, Content-Type: text/xml or application/xml
```

### Inspecting Sitemap Content
- Confirm that URLs in `<loc>` always use `https://www.cricbail.com` (not `http://` or IP addresses).
- Check that `<lastmod>` timestamps use W3C format (`YYYY-MM-DDThh:mm:ss+00:00` or `YYYY-MM-DD`).
- In `/news-sitemap.xml`, verify that only articles published within the last 48 hours are listed.

---

## 7. Google Search Console Procedures

### 1. Initial Verification
Verify domain ownership using DNS TXT record on your domain registrar (`cricbail.com`) or HTML file verification in `public/`.

### 2. Submitting Sitemaps
1. In Search Console, navigate to **Indexing** > **Sitemaps**.
2. Submit `sitemap.xml` under *Add a new sitemap*.
3. Submit `news-sitemap.xml` under *Add a new sitemap*.
4. Verify the status shows **Success** with valid URL counts.

### 3. Inspecting URLs
1. Paste any article URL into the top search bar (**URL Inspection**).
2. Click **Test Live URL** to confirm Googlebot can fetch the page.
3. Verify:
   - Crawl allowed? **Yes**
   - Page fetch: **Successful**
   - Canonical URL: Google-selected canonical matches user-declared canonical (`https://www.cricbail.com/news/...`).
   - Rich results detected: **NewsArticle**, **Breadcrumbs**.

### 4. Reviewing Page Indexing Reports
Navigate to **Indexing** > **Pages**:
- **Indexed:** URLs successfully indexed and eligible for search results.
- **Not Indexed Categories:**
  - *Crawled - currently not indexed:* Usually indicates thin content or duplicate topics. Review content depth.
  - *Discovered - currently not indexed:* Google knows of the URL but hasn't crawled yet. Typically resolves over time; verify internal linking from category pages.
  - *Page with redirect:* Normal for legacy 301 redirects (e.g. `/category/ipl-2026` &rarr; `/category/ipl`).
  - *Excluded by ‘noindex’ tag:* Correct and intentional for `/admin/*` routes.

---

## 8. Automated vs. Manual Review Matrix

| SEO Check | Automated | Manual Editorial | How Handled |
| :--- | :---: | :---: | :--- |
| **HTTP Status Codes (200 / 301 / 404)** | Yes | - | Verified by deployment script & regression test suite |
| **Canonical URL Consistency** | Yes | - | Enforced automatically by `SeoService` |
| **Duplicate Brand Suffix Removal** | Yes | - | Handled by `SeoService::title()` |
| **Structured Data Formatting** | Yes | - | Validated by regression tests & schema helpers |
| **Draft Content Sitemaps Isolation** | Yes | - | Filtered automatically by Eloquent scopes |
| **Robots Directives & Admin Blocking** | Yes | - | Enforced by `robots.txt` and admin Blade layouts |
| **Featured Image Availability** | Yes | - | Audited by `php artisan seo:audit` |
| **Thin Content (< 150 words)** | Yes | Yes | Flagged by audit; resolved editorially in admin |
| **Content Depth & Editorial Accuracy** | - | Yes | Editor verifies cricket analysis, stats, player names |
| **Search Intent & Headline Crafting** | - | Yes | Editor ensures catchy, accurate, non-clickbait titles |
| **Internal Linking Between Related Articles** | - | Yes | Author adds contextual links in article body |
| **Image Alt Text Accuracy** | - | Yes | Author provides descriptive caption and alt text |

---

## 9. Deployment Integration

Every code and content deployment executed via `scripts/deploy.sh` automatically performs:
1. **Pre-Deployment:**
   - Runs `php tests/verify_seo_system.php`. Halts on test failure.
   - Runs `php artisan seo:audit --fail-on-critical`. Halts on critical issues.
   - Logs non-critical editorial warnings.
2. **Post-Deployment:**
   - Curls production homepage (`https://www.cricbail.com`).
   - Verifies `robots.txt`, `sitemap.xml`, and `news-sitemap.xml`.
   - Tests sample article and quiz URLs for HTTP 200 OK.
   - Reports verification failure if production fails or is unreachable.
