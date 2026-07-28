# Angel Granites Next.js cPanel Migration

The new public website is a static Next.js export. It uses the existing image
library and a small allowlist of public PHP endpoints. It does not import,
package, link to, or call the legacy CRM.

## Build and package

```sh
NEXT_TELEMETRY_DISABLED=1 CI=1 npm run prepare:cpanel
npm run qa:cpanel
```

Outputs:

- `dist/cpanel/` — inspectable deployment directory
- `dist/cpanel-manifest.json` — local file inventory; never upload
- `dist/cpanel-PRESERVE_ON_SERVER.txt` — local deployment checklist; never upload

No ZIP is generated. Create the upload ZIP only after the site has been fully
tested and explicitly approved.

## Image sources

- Featured collection cards: `mobile_app/assets/featured_products.json`
- Collection galleries: verified files under `images/products/`
- Granite color SEO pages: verified, de-duplicated files under `images/colors/`
- Live color additions: read-only merge from the preserved
  `get_color_images.php` response
- Existing mobile color data remains owned by `api/color.json`,
  `update-colors-json.php` and `mobile_app/assets/color_asset_mapping.json`

The build throws an error when a featured collection cannot resolve to an
existing image. Granite colors are generated only from readable, non-empty
image files, prefer WebP when both formats exist, and preserve the established
color URLs that already have search value. The original image files are not
renamed or modified.

## Deployment safety

This is an overlay package. Do not enable FTP deletion, `rsync --delete`, or
mirror-delete behavior.

Before staging or production deployment:

1. Back up the current document root and database.
2. Read `dist/cpanel-PRESERVE_ON_SERVER.txt` beside the package.
3. Deploy to a staging subdomain first.
4. Extract the package over the staging document root without deleting
   server-only paths.
5. Run route, form, image, API, mobile and desktop checks.
6. Cut over production only after business UAT.

Automatic cPanel Git deployment is intentionally disabled. The generated
`dist/` directory is not committed, and syncing the repository root could
overwrite preserved applications and APIs. Production deployment must use a
reviewed ZIP created from `dist/cpanel/`.
