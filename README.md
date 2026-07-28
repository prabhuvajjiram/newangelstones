# Angel Stones platform

This repository contains the current Angel Granites public website, its cPanel
deployment overlay, the Angel Granites mobile app and the preserved operational
services that remain deployed beside the public site.

## Active project layout

- `apps/web/` — Next.js public website.
- `images/` — shared product, color, flyer and website media.
- `mobile_app/` — Flutter app for Apple, Android and Microsoft platforms.
- `api/` — mobile and preserved server APIs.
- `deploy/cpanel/` — production `.htaccess` and server-preservation notes.
- `scripts/` — static-export packaging and cPanel validation.
- `tests/` — current Next.js, staging and accessibility tests.
- `docs/` — migration, staging and production-preservation documentation.
- `contact-submit.php`, `inventory-proxy.php`, `update-sitemap.php` — PHP
  endpoints intentionally included in the public-site overlay.
- `crm/`, `chat/`, `creditapp/`, `giveaway/`, `forms/`, `app/`, `cache/` and
  related PHP utilities — separately deployed operational applications. These
  are preserved in place and are not included in the public-site package.
- `legacy-site/` — archived, retired public-theme code. It is not part of the
  current build or deployment.

## Development

```sh
npm install
npm run dev:web
```

Build the current public site:

```sh
npm run build:web
```

## cPanel production package

```sh
NEXT_TELEMETRY_DISABLED=1 CI=1 npm run prepare:cpanel
npm run qa:cpanel
```

The reviewed deployment directory is written to `dist/cpanel/`. Create a ZIP
from the contents of that directory only after QA and business approval.

Production deployment is an overlay. Never use delete or mirror-delete, and do
not overwrite or remove the preserved operational paths listed in
`deploy/cpanel/PRESERVE_ON_SERVER.txt`.

## Tests

The Playwright suites expect a built package served at the URL documented by
each suite:

```sh
npm run test:web:e2e
npm run test:accessibility
npm run test:staging
```

The Microsoft Store workflow remains under `.github/workflows/`. Automatic
whole-repository FTP deployment is retired because it could delete preserved
server applications.
