import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const scriptsDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(scriptsDirectory, "..");
const packageName = process.env.CPANEL_PACKAGE_NAME?.trim() || "cpanel";
const configuredBasePath = process.env.CPANEL_BASE_PATH?.trim() || "";
const basePath =
  configuredBasePath && configuredBasePath !== "/"
    ? `/${configuredBasePath.replace(/^\/+|\/+$/g, "")}`
    : "";
const packageDirectory = path.join(repositoryRoot, "dist", packageName);
const distributionDirectory = path.join(repositoryRoot, "dist");
const failures = [];

function walk(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const absolute = path.join(directory, entry.name);
    return entry.isDirectory() ? walk(absolute) : [absolute];
  });
}

function relative(file) {
  return path.relative(packageDirectory, file).split(path.sep).join("/");
}

if (!fs.existsSync(packageDirectory)) {
  throw new Error("cPanel test directory does not exist. Run npm run prepare:cpanel.");
}

const files = walk(packageDirectory);
const relativeFiles = new Set(files.map(relative));
for (const file of relativeFiles) {
  if (
    file === ".DS_Store" ||
    file.includes("/.DS_Store") ||
    path.basename(file).startsWith("._") ||
    file === "__MACOSX" ||
    file.startsWith("__MACOSX/")
  ) {
    failures.push(`macOS metadata is present in the package: ${file}`);
  }
}
for (const legacyLegalFile of [
  "privacy-policy.html",
  "terms-of-service.html",
  "sms-terms.html"
]) {
  if (relativeFiles.has(legacyLegalFile)) {
    failures.push(`Legacy unthemed legal page is still packaged: ${legacyLegalFile}`);
  }
}

if ([...relativeFiles].some((file) => file === "crm" || file.startsWith("crm/"))) {
  failures.push("The cPanel package contains a crm path.");
}

for (const preservedServerFile of [
  "PRESERVE_ON_SERVER.txt",
  "package-manifest.json",
  "get_directory_files.php",
  "get_color_images.php",
  "update-colors-json.php",
  "clear-cache.php",
  "serve_image.php",
  "api/color.json",
  "api/generate-jsonld.php",
  "api/get_directory_files_seo.php",
  "api/mobile-config.php",
  "api/mobile-config.json",
  "api/search-handler.php",
  "api/specials.php",
  "api/promotions.json",
  "api/shipping_endpoints.php",
  "includes/SpecialsManager.php"
]) {
  if (relativeFiles.has(preservedServerFile)) {
    failures.push(
      `Production overlay would overwrite a preserved server file: ${preservedServerFile}`
    );
  }
}
for (const preservedServerPrefix of [
  "crm/",
  "cache/",
  "mobile_app/",
  "api/",
  "app/"
]) {
  if ([...relativeFiles].some((file) => file.startsWith(preservedServerPrefix))) {
    failures.push(
      `Production overlay contains preserved server path: ${preservedServerPrefix}`
    );
  }
}

for (const browserConfigAsset of [
  "browserconfig.xml",
  "ms-icon-70x70.png",
  "ms-icon-150x150.png",
  "ms-icon-310x310.png"
]) {
  if (!relativeFiles.has(browserConfigAsset)) {
    failures.push(`Missing browser configuration asset: ${browserConfigAsset}`);
  }
}
const browserConfig = fs.readFileSync(
  path.join(packageDirectory, "browserconfig.xml"),
  "utf8"
);
for (const icon of [
  "ms-icon-70x70.png",
  "ms-icon-150x150.png",
  "ms-icon-310x310.png"
]) {
  const expectedPath = `${basePath}/${icon}`;
  if (!browserConfig.includes(`src="${expectedPath}"`)) {
    failures.push(
      `browserconfig.xml is missing the expected icon path ${expectedPath}.`
    );
  }
}

const publicCode = files.filter((file) =>
  /\.(?:html|js|css|txt|json)$/i.test(file)
);
for (const file of publicCode) {
  const contents = fs.readFileSync(file, "utf8");
  if (/(?:["'(]|https?:\/\/[^"']+)(?:\/crm\/|crm\/)/i.test(contents)) {
    failures.push(`Public output references CRM: ${relative(file)}`);
  }
}
if (
  !publicCode.some((file) =>
    fs.readFileSync(file, "utf8").includes("/get_directory_files.php")
  )
) {
  failures.push(
    "Public output is missing the preserved live product-image directory integration."
  );
}
if (
  !publicCode.some((file) =>
    fs.readFileSync(file, "utf8").includes("/get_color_images.php")
  )
) {
  failures.push(
    "Public output is missing the read-only preserved color-image integration."
  );
}

const htmlFiles = files.filter((file) => file.endsWith(".html"));
const localReferences = new Set();
const rewrittenAliases = new Set([
  "privacy-policy",
  "terms-of-service",
  "sms-terms"
]);
for (const file of htmlFiles) {
  const contents = fs.readFileSync(file, "utf8");
  const referencePattern = /\b(?:src|href)=["']([^"']+)["']/gi;
  for (const match of contents.matchAll(referencePattern)) {
    const raw = match[1];
    if (
      !raw ||
      raw.startsWith("#") ||
      raw.startsWith("data:") ||
      raw.startsWith("mailto:") ||
      raw.startsWith("tel:") ||
      raw.startsWith("javascript:") ||
      /^https?:\/\//i.test(raw)
    ) {
      continue;
    }
    let clean = decodeURIComponent(raw.split(/[?#]/)[0]).replace(/^\/+/, "");
    const basePathRelative = basePath.replace(/^\/+/, "");
    if (
      basePathRelative &&
      (clean === basePathRelative || clean.startsWith(`${basePathRelative}/`))
    ) {
      clean = clean.slice(basePathRelative.length).replace(/^\/+/, "");
    }
    if (!clean) continue;
    if (clean.endsWith("/")) {
      localReferences.add(`${clean}index.html`);
    } else {
      localReferences.add(clean);
    }
  }
}

for (const reference of localReferences) {
  if (
    !relativeFiles.has(reference) &&
    !relativeFiles.has(`${reference}/index.html`) &&
    !rewrittenAliases.has(reference.replace(/\/$/, "")) &&
    !reference.endsWith(".php")
  ) {
    failures.push(`Missing local HTML reference: ${reference}`);
  }
}

const silkBluePagePath = path.join(
  packageDirectory,
  "colors/blue-silk-granite/index.html"
);
if (fs.existsSync(silkBluePagePath)) {
  const silkBluePage = fs.readFileSync(silkBluePagePath, "utf8");
  for (const expected of [
    "Silk Blue Granite for Monuments &amp; Headstones",
    "Blue Silk Granite",
    "https://www.theangelstones.com/colors/blue-silk-granite/"
  ]) {
    if (!silkBluePage.includes(expected)) {
      failures.push(`Silk Blue SEO page is missing: ${expected}`);
    }
  }
} else {
  failures.push("Silk Blue canonical color page is missing.");
}

const requiredPhonePlacements = [
  ["contact/index.html", "+1 706-262-7177"],
  ["locations/elberton-ga/index.html", "+1 706-262-7177"]
];
for (const [relativePath, phone] of requiredPhonePlacements) {
  const absolutePath = path.join(packageDirectory, relativePath);
  if (
    !fs.existsSync(absolutePath) ||
    !fs.readFileSync(absolutePath, "utf8").includes(phone)
  ) {
    failures.push(
      `${relativePath} is missing the alternative office phone ${phone}.`
    );
  }
}

const siteOrigin = "https://www.theangelstones.com";
const indexableSeoRows = [];

function attribute(tag, name) {
  return (
    tag.match(new RegExp(`\\b${name}=["']([^"']*)["']`, "i"))?.[1]?.trim() ?? ""
  );
}

function visitStructuredData(value, visitor) {
  if (Array.isArray(value)) {
    value.forEach((item) => visitStructuredData(item, visitor));
    return;
  }
  if (!value || typeof value !== "object") return;
  visitor(value);
  Object.values(value).forEach((item) => visitStructuredData(item, visitor));
}

for (const file of htmlFiles) {
  const contents = fs.readFileSync(file, "utf8");
  const robotsTag = contents
    .match(/<meta\b[^>]*\bname=["']robots["'][^>]*>/i)?.[0];
  if (robotsTag && /noindex/i.test(attribute(robotsTag, "content"))) continue;

  const page = relative(file);
  const title = contents.match(/<title>([^<]+)<\/title>/i)?.[1]?.trim() ?? "";
  const descriptionTag = contents
    .match(/<meta\b[^>]*\bname=["']description["'][^>]*>/i)?.[0];
  const canonicalTag = contents
    .match(/<link\b[^>]*\brel=["']canonical["'][^>]*>/i)?.[0];
  const metaTags = [...contents.matchAll(/<meta\b[^>]*>/gi)].map(
    (match) => match[0]
  );
  const property = (name) =>
    metaTags.find((tag) => attribute(tag, "property") === name);
  const namedMeta = (name) =>
    metaTags.find((tag) => attribute(tag, "name") === name);
  const canonical = canonicalTag ? attribute(canonicalTag, "href") : "";
  const expectedPath =
    page === "index.html"
      ? "/"
      : page.endsWith("/index.html")
        ? `/${page.slice(0, -"/index.html".length)}/`
        : `/${page}`;
  const expectedCanonical = `${siteOrigin}${expectedPath}`;
  const h1Count = (contents.match(/<h1\b/gi) ?? []).length;

  if (!title) failures.push(`SEO title is missing: ${page}`);
  if (!descriptionTag || !attribute(descriptionTag, "content")) {
    failures.push(`SEO description is missing: ${page}`);
  }
  if (canonical !== expectedCanonical) {
    failures.push(
      `Canonical mismatch: ${page} -> ${canonical || "missing"} (expected ${expectedCanonical})`
    );
  }
  if (h1Count !== 1) {
    failures.push(`Expected exactly one H1 on ${page}, found ${h1Count}.`);
  }
  for (const requiredProperty of [
    "og:title",
    "og:description",
    "og:url",
    "og:image"
  ]) {
    if (!property(requiredProperty)) {
      failures.push(`Missing ${requiredProperty}: ${page}`);
    }
  }
  if (
    property("og:url") &&
    attribute(property("og:url"), "content") !== canonical
  ) {
    failures.push(`Open Graph URL does not match canonical: ${page}`);
  }
  if (!namedMeta("twitter:card")) {
    failures.push(`Twitter card metadata is missing: ${page}`);
  }

  const jsonLdBlocks = [
    ...contents.matchAll(
      /<script\b[^>]*\btype=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi
    )
  ];
  if (!jsonLdBlocks.length) {
    failures.push(`Structured data is missing: ${page}`);
  }
  const parsedJsonLd = [];
  jsonLdBlocks.forEach((block, index) => {
    try {
      const structuredData = JSON.parse(block[1]);
      parsedJsonLd.push(structuredData);
      visitStructuredData(structuredData, (item) => {
        const types = Array.isArray(item["@type"])
          ? item["@type"]
          : [item["@type"]];
        if (
          types.includes("Product") &&
          !item.offers &&
          !item.review &&
          !item.aggregateRating
        ) {
          failures.push(
            `Product structured data lacks offers, review or aggregateRating: ${page}`
          );
        }
      });
    } catch {
      failures.push(`Invalid JSON-LD block ${index + 1}: ${page}`);
    }
  });
  if (
    /^(?:colors|designs)\/[^/]+\/index\.html$/.test(page) &&
    !parsedJsonLd.some((item) => item?.["@type"] === "ItemPage")
  ) {
    failures.push(`Reference detail page is missing ItemPage schema: ${page}`);
  }

  const images = [...contents.matchAll(/<img\b[^>]*>/gi)].map(
    (match) => match[0]
  );
  if (images.some((image) => !/\balt=["'][^"']*["']/i.test(image))) {
    failures.push(`Rendered image is missing alt text: ${page}`);
  }

  indexableSeoRows.push({
    page,
    title,
    description: descriptionTag ? attribute(descriptionTag, "content") : "",
    canonical
  });
}

for (const field of ["title", "description", "canonical"]) {
  const values = new Map();
  for (const row of indexableSeoRows) {
    if (!row[field]) continue;
    const existing = values.get(row[field]);
    if (existing) {
      failures.push(
        `Duplicate SEO ${field}: ${existing} and ${row.page}`
      );
    } else {
      values.set(row[field], row.page);
    }
  }
}

const sitemapContents = fs.readFileSync(
  path.join(packageDirectory, "sitemap.xml"),
  "utf8"
);
for (const row of indexableSeoRows) {
  if (!sitemapContents.includes(`<loc>${row.canonical}</loc>`)) {
    failures.push(`Indexable canonical is missing from sitemap: ${row.page}`);
  }
}
if (sitemapContents.includes("/discovered.html")) {
  failures.push("Legacy discovered.html is still present in the sitemap.");
}

const requiredRoutes = [
  "index.html",
  "monuments/index.html",
  "inventory/index.html",
  "flyers/index.html",
  "privacy-policy/index.html",
  "terms-of-service/index.html",
  "sms-terms/index.html",
  "granite-colors/index.html",
  "products-services/index.html",
  "locations/index.html",
  "locations/elberton-ga/index.html",
  "locations/barre-vt/index.html",
  "resources/index.html",
  "resources/monument-glossary/index.html",
  "resources/common-monument-shapes/index.html",
  "resources/granite-monument-finishes/index.html",
  "benches/index.html",
  "designs/index.html",
  "columbarium/index.html",
  "mbna-2025/index.html",
  "contact/index.html",
  "sitemap.xml",
  "robots.txt",
  "llms.txt",
  "update-sitemap.php",
  ".htaccess"
];
for (const route of requiredRoutes) {
  if (!relativeFiles.has(route)) failures.push(`Required package file missing: ${route}`);
}

const htaccess = fs.readFileSync(path.join(packageDirectory, ".htaccess"), "utf8");
const expectedErrorDocument = `ErrorDocument 404 ${basePath}/404.html`;
if (!htaccess.includes(expectedErrorDocument)) {
  failures.push(`404 error document is not configured as ${expectedErrorDocument}.`);
}
if (!htaccess.includes("RewriteRule ^ - [R=404,L]")) {
  failures.push("Unknown routes are not configured to return a genuine HTTP 404.");
}
if (htaccess.includes("RewriteRule ^ 404.html [L]")) {
  failures.push("Legacy soft-404 rewrite is still present.");
}
if (!htaccess.includes("AddType text/plain .txt")) {
  failures.push("Plain-text MIME configuration is missing for llms.txt.");
}
if (
  !htaccess.includes(
    '<FilesMatch "^(?:(?:.*-)?PRESERVE_ON_SERVER\\.txt|(?:package|cpanel(?:-new)?)-manifest\\.json)$">'
  )
) {
  failures.push("Defense-in-depth blocking for private release artifacts is missing.");
}
if (
  htaccess.includes(
    '<FilesMatch "\\.(?:css|js|jpg|jpeg|png|gif|webp|svg|ico|woff|woff2|ttf|otf|mp4|webm)$">'
  )
) {
  failures.push("Mutable images or videos still receive immutable one-year caching.");
}

const inventoryProxy = fs.readFileSync(
  path.join(packageDirectory, "inventory-proxy.php"),
  "utf8"
);
if (
  inventoryProxy.includes("CURLOPT_SSL_VERIFYPEER, false") ||
  inventoryProxy.includes("CURLOPT_SSL_VERIFYHOST => false")
) {
  failures.push("Inventory proxy disables upstream TLS certificate verification.");
}
if (
  !inventoryProxy.includes("CURLOPT_SSL_VERIFYPEER => true") ||
  !inventoryProxy.includes("CURLOPT_SSL_VERIFYHOST => 2")
) {
  failures.push("Inventory list request is missing strict TLS verification.");
}
if (!inventoryProxy.includes("min(1000")) {
  failures.push("Inventory API page size is not bounded.");
}

const contactSubmit = fs.readFileSync(
  path.join(packageDirectory, "contact-submit.php"),
  "utf8"
);
if (
  !contactSubmit.includes("$mauticHttpCode < 200") ||
  !contactSubmit.includes("$mauticHttpCode >= 300")
) {
  failures.push("Contact handler does not reject failed Mautic HTTP responses.");
}

const llmsContents = fs.readFileSync(
  path.join(packageDirectory, "llms.txt"),
  "utf8"
);
if (!/^#\s+\S+/m.test(llmsContents)) {
  failures.push("llms.txt is missing its required Markdown H1.");
}
if ((llmsContents.match(/\[[^\]]+\]\(https:\/\/www\.theangelstones\.com\/[^)]*\)/g) ?? []).length < 5) {
  failures.push("llms.txt does not contain enough canonical site links.");
}
if (/<(?:html|head|body)\b/i.test(llmsContents)) {
  failures.push("llms.txt contains HTML instead of a Markdown site summary.");
}

for (const legalSlug of ["privacy-policy", "terms-of-service", "sms-terms"]) {
  const redirectTarget = `${basePath}/${legalSlug}/`;
  if (
    !htaccess.includes(
      `RewriteRule ^${legalSlug}\\.html$ ${redirectTarget} [R=301,L]`
    )
  ) {
    failures.push(`Legacy legal redirect is missing for ${legalSlug}.html`);
  }
}
if (
  !htaccess.includes(
    `RewriteRule ^discovered\\.html$ ${basePath}/monuments/ [R=301,L]`
  )
) {
  failures.push("Legacy discovered.html redirect to /monuments/ is missing.");
}
if (
  !htaccess.includes(
    `RewriteRule ^colors/granite/?$ ${basePath}/granite-colors/ [R=301,L]`
  )
) {
  failures.push("Legacy generic granite-color redirect is missing.");
}
for (const [retiredColor, primaryColor] of [
  ["forest-green", "rain-forest-green"],
  ["galaxy", "galaxy-black"],
  ["green", "green-breeze"],
  ["green-dream", "tropical-green"],
  ["jet-black", "premium-black"],
  ["nh-red", "strawberry-red"],
  ["oriental-green", "sanfrancisco-green"],
  ["silk-blue", "blue-silk"],
  ["white-and-red", "redwood-red-cats-eye"]
]) {
  if (
    !htaccess.includes(
      `RewriteRule ^colors/${retiredColor}-granite/?$ ${basePath}/colors/${primaryColor}-granite/ [R=301,L]`
    )
  ) {
    failures.push(`Retired color redirect is missing: ${retiredColor}`);
  }
}
if (
  !htaccess.includes(
    `RewriteRule ^colors/picasso-granite/?$ ${basePath}/colors/green-wave-quartzite/ [R=301,L]`
  )
) {
  failures.push("Retired Picasso redirect to Green Wave Quartzite is missing.");
}
for (const [legacyCategory, route] of [
  ["mbna_2025", "mbna-2025"],
  ["monuments", "monuments"],
  ["columbarium", "columbarium"],
  ["designs", "designs"],
  ["benches", "benches"]
]) {
  if (
    !htaccess.includes(`category=${legacyCategory}`) ||
    !htaccess.includes(
      `RewriteRule ^$ ${basePath}/${route}/ [R=301,L,QSD]`
    )
  ) {
    failures.push(`Legacy category redirect is missing: ${legacyCategory}`);
  }
}

const imageReferences = new Set();
for (const file of htmlFiles) {
  const contents = fs.readFileSync(file, "utf8");
  for (const match of contents.matchAll(/<img\b[^>]*\bsrc=["']([^"']+)["']/gi)) {
    const raw = match[1];
    if (/^https?:\/\//i.test(raw)) continue;
    let image = decodeURIComponent(raw.split(/[?#]/)[0]).replace(/^\/+/, "");
    const basePathRelative = basePath.replace(/^\/+/, "");
    if (
      basePathRelative &&
      (image === basePathRelative || image.startsWith(`${basePathRelative}/`))
    ) {
      image = image.slice(basePathRelative.length).replace(/^\/+/, "");
    }
    imageReferences.add(image);
  }
}
for (const image of imageReferences) {
  if (!relativeFiles.has(image)) failures.push(`Rendered image missing: ${image}`);
}

const colorPages = [...relativeFiles].filter(
  (file) => /^colors\/[^/]+\/index\.html$/.test(file)
);
const normalizeColorStem = (filename) => {
  let stem = filename
    .replace(/\.[^.]+$/, "")
    .toLowerCase()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");
  if (stem === "bluepearl") stem = "blue-pearl";
  if (stem === "pacific-grey") stem = "pacific-gray";
  return stem;
};
const colorStems = new Set(
  [...relativeFiles]
    .filter((file) => /^images\/colors\/[^/]+\.(?:jpe?g|png|webp)$/i.test(file))
    .map((file) => normalizeColorStem(path.basename(file)))
);
const expectedColorPages = new Set(
  [...colorStems].map((stem) =>
    /(?:quartzite|marble|sandstone)$/.test(stem)
      ? `colors/${stem}/index.html`
      : `colors/${stem}-granite/index.html`
  )
);
for (const expectedColorPage of expectedColorPages) {
  if (!relativeFiles.has(expectedColorPage)) {
    failures.push(`Expected color page is missing: ${expectedColorPage}`);
  }
}
if (colorPages.length !== expectedColorPages.size) {
  failures.push(
    `Expected ${expectedColorPages.size} unique color pages from the image library, found ${colorPages.length}.`
  );
}
const designPages = [...relativeFiles].filter(
  (file) => /^designs\/[^/]+\/index\.html$/.test(file)
);
if (designPages.length !== 10) {
  failures.push(`Expected 10 curated design pages, found ${designPages.length}.`);
}

const htaccessCount = [...relativeFiles].filter((file) => path.basename(file) === ".htaccess").length;
if (htaccessCount !== 1) {
  failures.push(`Expected exactly one .htaccess, found ${htaccessCount}.`);
}
if (basePath && !htaccess.includes('Header set X-Robots-Tag "noindex, nofollow"')) {
  failures.push("Staging package is missing the X-Robots-Tag noindex header.");
}
if (basePath) {
  const webManifest = JSON.parse(
    fs.readFileSync(path.join(packageDirectory, "manifest.webmanifest"), "utf8")
  );
  if (webManifest.start_url !== `${basePath}/`) {
    failures.push(
      `Staging web manifest start_url is ${webManifest.start_url}; expected ${basePath}/.`
    );
  }

  if (
    !inventoryProxy.includes(
      "dirname(__DIR__, 2) . '/private/monument_business_api_key'"
    )
  ) {
    failures.push(
      "Staging inventory proxy does not resolve the account-level private API key."
    );
  }
}

for (const privateReleaseArtifact of [
  `${packageName}-manifest.json`,
  `${packageName}-PRESERVE_ON_SERVER.txt`
]) {
  if (!fs.existsSync(path.join(distributionDirectory, privateReleaseArtifact))) {
    failures.push(`Private release artifact is missing: ${privateReleaseArtifact}`);
  }
}

for (const file of files) {
  const mode = fs.statSync(file).mode & 0o777;
  if (mode !== 0o644) failures.push(`File permission is not 0644: ${relative(file)}`);
}

if (failures.length) {
  console.error(`cPanel QA failed with ${failures.length} issue(s):`);
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}

console.log("cPanel QA passed");
console.log(`HTML pages: ${htmlFiles.length}`);
console.log(`Granite color pages: ${colorPages.length}`);
console.log(`Curated design pages: ${designPages.length}`);
console.log(`Referenced images verified: ${imageReferences.size}`);
console.log(`Package files: ${relativeFiles.size}`);
console.log(`Base path: ${basePath || "/"}`);
console.log("CRM paths/references: 0");
console.log("File permissions: 0644");
