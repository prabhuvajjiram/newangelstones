# Retired public-site archive

This directory preserves the previous PHP/HTML public theme for source history
and emergency reference. It is not part of the current Next.js build, cPanel
package or production deployment.

Contents:

- `public-theme/` — retired homepage, metadata routes, legal pages and generated
  public files.
- `config/` — retired root deployment and performance configuration.
- `docs/` — retired deployment instructions.
- `tests/` — tests that targeted the retired homepage DOM.
- `tools/` — retired theme, thumbnail and payment-test utilities.
- `unused-icons/` — favicon variants no longer referenced by the current site.
- `workflows/` — disabled historical whole-root FTP deployment workflow.

Some older-looking files remain at the repository root intentionally. The
mobile app, APIs, CRM, order/quote utilities, image discovery and cache
endpoints, specials, giveaway and other preserved server applications still
depend on their established production paths. They must not be moved or
deleted as part of public-theme cleanup.

Do not deploy this directory to `public_html`. Production packages must be
created only from `dist/cpanel/`.
