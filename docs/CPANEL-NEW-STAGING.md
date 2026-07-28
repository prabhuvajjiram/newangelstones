# Testing in `public_html/new`

Use the dedicated staging package for the lowercase URL:

`https://www.theangelstones.com/new/`

## Build and verify

```bash
npm run prepare:cpanel:new
npm run qa:cpanel:new
```

Upload the **contents** of `dist/cpanel-new` into:

`public_html/new`

Do not upload the `cpanel-new` folder itself inside `new`.

The staging package is built with `/new` as its base path. Its local images,
Next.js assets, contact handler, and inventory proxy all remain under `/new`.
The included `.htaccess` adds `X-Robots-Tag: noindex, nofollow` to prevent the
temporary copy from being indexed.

The deployment checklist and package manifest are deliberately generated
outside the public package:

- `dist/cpanel-new-PRESERVE_ON_SERVER.txt`
- `dist/cpanel-new-manifest.json`

Read them locally; do not upload either file into `public_html/new`.

## Production after approval

Do not move the `/new` build into the production root. Build the root package:

```bash
npm run prepare:cpanel
npm run qa:cpanel
```

Upload the contents of `dist/cpanel` into `public_html`, while preserving the
server-only files listed in `dist/cpanel-PRESERVE_ON_SERVER.txt`.

Delete `public_html/new` only after the production site has been verified.

This repository uses reviewed ZIP overlays for cPanel. Automatic cPanel Git
deployment is intentionally disabled because generated `dist/` artifacts are
not committed and a source-root sync could overwrite preserved applications.
