# Angel Stones public-site preservation audit

Audit date: 2026-07-27

## Purpose

This checklist compares the new static Next.js public site with the live legacy
homepage, archived `legacy-site/public-theme/index.html`, public PHP utilities
and the repository history.
The deployment remains an overlay: it must not delete server-only applications,
credentials, uploads, generated documents or operational data.

## Public experience coverage

| Legacy capability | New public-site coverage | Status |
| --- | --- | --- |
| Quarry/manufacturing hero video | Responsive WebM/MP4 hero with poster fallback | Covered |
| Dealer/wholesale positioning | Homepage, products and services, Elberton and Barre pages | Covered and expanded |
| Special flyers and downloadable PDFs | Dedicated `/flyers/` page and homepage section | Covered |
| Featured monument collections | Benches, in-stock, columbarium, designs and MBNA collections | Covered |
| Product/design search | Collection search plus curated crawlable design detail pages | Covered and expanded |
| Granite color browsing | 63 unique crawlable color pages, family navigation, inventory deep links and redirects from retired aliases | Covered and expanded |
| Current inventory API | Dedicated inventory proxy, intelligent search, images-first results and detail dialog | Covered and expanded |
| Products and finishing services | Dedicated `/products-services/` page | Covered and expanded |
| Contact/quote form | Themed contact page using the existing protected PHP endpoint | Covered |
| Elberton, Barre, mailing and corporate addresses | Contact and location pages | Covered and expanded |
| Clover invoice payment | Direct Clover link in utility header and footer | Covered |
| iOS and Android downloads | Device-aware fixed download prompt | Covered |
| Chat support | Deferred Tawk chat loader | Covered |
| Facebook and Instagram | Footer links | Restored |
| Privacy, terms and SMS terms | Themed canonical routes with redirects from legacy HTML URLs | Covered and expanded |
| Learning/reference content | Glossary, shapes, finishes, dimensions and ordering checklist | New SEO expansion |
| Legacy dynamic promotions | Current downloadable flyers remain public; expired/CRM-backed promotion admin is not migrated | Deliberately retired from public UI |
| Legacy payment confirmation and receipt flow | Direct Clover payment remains; legacy confirmation flow is not migrated | Deliberately retired |

## Operational applications preserved on the server

These are not part of the public Next.js package and must remain untouched
during an overlay deployment:

- `/crm/`
- `/chat/`
- `/creditapp/`
- `/giveaway/`
- `/forms/`
- `/app/` and `/mobile_app/`
- `/uploads/`, `/logs/`, `/cache/` and `/.well-known/`
- shipment, promotion, order/quote, email, document and server-only PHP utilities
- configuration, credentials, tokens, databases and generated documents

The package `.htaccess` serves existing files and directories before its static
404 fallback, so preserved operational routes remain reachable. Search crawlers
are instructed not to index operational folders or PHP endpoints.

## Cron and generated SEO behavior

- `update-sitemap.php` is included in the overlay because the old cron version
  would restore retired URLs and omit new pages.
- The replacement is CLI-only, scans the deployed static route set, validates
  every canonical, refuses to overwrite the sitemap when fewer than 50 public
  pages are present, validates XML and uses an atomic file replacement.
- It includes color, design, resource and location detail routes and ignores
  operational applications.
- `update-colors-json.php` can continue to support legacy/mobile data, but it
  cannot create a new static color-detail HTML page. Adding a public color
  requires a fresh Next.js build and overlay package.

## Deployment guardrails

- Do not deploy with `--delete`, mirror-delete or a cPanel file-manager option
  that removes files absent from the package.
- Automatic whole-repository cPanel deployment is disabled. Production must
  overlay only the reviewed contents of `dist/cpanel/`.
- Do not create or upload a ZIP until browser testing is approved.
- Never include `/crm/` in the package.
