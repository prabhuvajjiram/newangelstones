# Dependency audit note

Audit date: 2026-07-27

## Current result

`npm audit --omit=dev --audit-level=high` reports three high-severity findings
through Next.js 16.2.12:

- Next.js pins PostCSS 8.4.31. PostCSS versions through 8.5.17 are affected by
  source-map path traversal and related untrusted-CSS issues.
- Next.js optionally installs Sharp 0.34.5. Sharp versions before 0.35.0 use
  vulnerable libvips builds when processing untrusted image input.

The repository is already using the latest stable Next.js release available on
the audit date. `npm audit fix --force` proposes a breaking downgrade to
Next.js 14.2.35 and must not be applied.

## Exposure assessment

The production website is a static export. It does not run a Next.js server,
accept user-supplied CSS for PostCSS, or process user-uploaded images with the
Next.js Sharp dependency. Public uploads and operational APIs remain outside
the static build. The cPanel packaging script directly uses patched Sharp
0.35.3 against reviewed repository images.

This substantially limits production exposure, but it does not make the
dependency alerts disappear from the development/build environment.

## Follow-up

- Upgrade Next.js when a stable release updates its PostCSS and Sharp
  dependencies.
- Re-run `npm explain postcss`, `npm explain sharp`, the production build,
  cPanel QA and browser/accessibility suites after that upgrade.
- Do not add an unverified override or run `npm audit fix --force`; both can
  invalidate the working Next.js dependency tree.

Advisories:

- https://github.com/advisories/GHSA-r28c-9q8g-f849
- https://github.com/advisories/GHSA-6g55-p6wh-862q
- https://github.com/advisories/GHSA-f88m-g3jw-g9cj
