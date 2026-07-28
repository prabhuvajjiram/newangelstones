import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const scriptsDirectory = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(scriptsDirectory, "..");
const exportDirectory = path.join(repositoryRoot, "apps/web/out");
const distributionDirectory = path.join(repositoryRoot, "dist");
const packageName = process.env.CPANEL_PACKAGE_NAME?.trim() || "cpanel";
const configuredBasePath = process.env.CPANEL_BASE_PATH?.trim() || "";
const basePath =
  configuredBasePath && configuredBasePath !== "/"
    ? `/${configuredBasePath.replace(/^\/+|\/+$/g, "")}`
    : "";
const packageDirectory = path.join(distributionDirectory, packageName);

if (!fs.existsSync(path.join(exportDirectory, "index.html"))) {
  throw new Error("Next.js export is missing. Run npm run build:web:cpanel first.");
}

fs.rmSync(packageDirectory, { recursive: true, force: true });
fs.mkdirSync(packageDirectory, { recursive: true });
fs.cpSync(exportDirectory, packageDirectory, { recursive: true });

function copy(relativeSource, relativeDestination = relativeSource) {
  const source = path.join(repositoryRoot, relativeSource);
  const destination = path.join(packageDirectory, relativeDestination);
  if (!fs.existsSync(source)) {
    throw new Error(`Required cPanel package source is missing: ${relativeSource}`);
  }
  fs.mkdirSync(path.dirname(destination), { recursive: true });
  fs.cpSync(source, destination, { recursive: true });
}

copy("images");

[
  "favicon.ico",
  "favicon-16x16.png",
  "favicon-32x32.png",
  "favicon-96x96.png",
  "apple-icon-180x180.png",
  "android-icon-192x192.png",
  "browserconfig.xml",
  "ms-icon-70x70.png",
  "ms-icon-150x150.png",
  "ms-icon-310x310.png",
  "angel-granite-stones.jpg",
  "llms.txt",
  "contact-submit.php",
  "inventory-proxy.php",
  "update-sitemap.php"
].forEach((file) => copy(file));

copy("deploy/cpanel/.htaccess", ".htaccess");

function walk(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const absolute = path.join(directory, entry.name);
    return entry.isDirectory() ? walk(absolute) : [absolute];
  });
}

function addBasePathToPublicReferences(contents) {
  if (!basePath) return contents;

  const publicPaths = [
    "/images/",
    "/inventory-proxy.php",
    "/contact-submit.php",
    "/update-sitemap.php",
    "/favicon.ico",
    "/favicon-16x16.png",
    "/favicon-32x32.png",
    "/favicon-96x96.png",
    "/apple-icon-180x180.png",
    "/android-icon-192x192.png",
    "/browserconfig.xml",
    "/ms-icon-70x70.png",
    "/ms-icon-150x150.png",
    "/ms-icon-310x310.png",
    "/angel-granite-stones.jpg"
  ];

  let rewritten = contents;
  for (const publicPath of publicPaths) {
    const escaped = publicPath.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    rewritten = rewritten.replace(
      new RegExp(`([="'\`(])${escaped}`, "g"),
      `$1${basePath}${publicPath}`
    );
  }
  return rewritten;
}

function prepareSubdirectoryPackage() {
  if (!basePath) return;

  const textFiles = walk(packageDirectory).filter(
    (file) =>
      /\.(?:css|html|js|json|txt|webmanifest|xml)$/i.test(file) ||
      path.basename(file) === ".htaccess"
  );
  for (const file of textFiles) {
    const contents = fs.readFileSync(file, "utf8");
    let rewritten = addBasePathToPublicReferences(contents);
    if (path.basename(file) === "manifest.webmanifest") {
      rewritten = rewritten.replace(
        /"start_url"\s*:\s*"\/"/,
        `"start_url":"${basePath}/"`
      );
    }
    if (rewritten !== contents) fs.writeFileSync(file, rewritten);
  }

  const inventoryProxyPath = path.join(
    packageDirectory,
    "inventory-proxy.php"
  );
  let inventoryProxy = fs.readFileSync(inventoryProxyPath, "utf8");
  inventoryProxy = inventoryProxy.replace(
    "dirname(__DIR__) . '/private/monument_business_api_key'",
    "dirname(__DIR__, 2) . '/private/monument_business_api_key'"
  );
  fs.writeFileSync(inventoryProxyPath, inventoryProxy);

  const htaccessPath = path.join(packageDirectory, ".htaccess");
  let htaccess = fs.readFileSync(htaccessPath, "utf8");
  htaccess = htaccess
    .replace("ErrorDocument 404 /404.html", `ErrorDocument 404 ${basePath}/404.html`)
    .replace(
      /^(RewriteRule\s+\S+\s+)\/(?!\/)(\S+)/gm,
      `$1${basePath}/$2`
    )
    .replace(
      "<IfModule mod_headers.c>",
      `<IfModule mod_headers.c>\n  Header set X-Robots-Tag "noindex, nofollow"`
    );
  fs.writeFileSync(htaccessPath, htaccess);
}

const inventoryProductDirectory = path.join(repositoryRoot, "images/products");
const inventoryThumbnailDirectory = path.join(
  packageDirectory,
  "images/inventory-thumbnails"
);
const thumbnailSources = walk(inventoryProductDirectory).filter((file) =>
  /\.(?:avif|gif|jpe?g|png|webp)$/i.test(file)
);

for (const source of thumbnailSources) {
  const relative = path.relative(inventoryProductDirectory, source);
  const parsed = path.parse(relative);
  const destination = path.join(
    inventoryThumbnailDirectory,
    parsed.dir,
    `${parsed.name}.webp`
  );
  fs.mkdirSync(path.dirname(destination), { recursive: true });
  await sharp(source)
    .rotate()
    .resize(220, 220, { fit: "inside", withoutEnlargement: true })
    .webp({ quality: 72, effort: 4 })
    .toFile(destination);
}

prepareSubdirectoryPackage();

for (const file of walk(packageDirectory)) {
  const name = path.basename(file);
  if (name === ".DS_Store" || name.startsWith("._")) {
    fs.rmSync(file);
  }
}

for (const directory of [packageDirectory, ...walkDirectories(packageDirectory)]) {
  fs.chmodSync(directory, 0o755);
}
for (const file of walk(packageDirectory)) {
  fs.chmodSync(file, 0o644);
}

function walkDirectories(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (!entry.isDirectory()) return [];
    const absolute = path.join(directory, entry.name);
    return [absolute, ...walkDirectories(absolute)];
  });
}

const files = walk(packageDirectory)
  .map((file) => path.relative(packageDirectory, file))
  .sort();
const manifest = {
  generatedAt: new Date().toISOString(),
  deploymentMode: basePath
    ? `static Next.js export for ${basePath} with CRM-free PHP endpoint overlay`
    : "static Next.js export with CRM-free PHP endpoint overlay",
  basePath: basePath || "/",
  fileCount: files.length,
  excludesCRM: !files.some(
    (file) => file === "crm" || file.startsWith(`crm${path.sep}`)
  ),
  files
};
const manifestPath = path.join(distributionDirectory, `${packageName}-manifest.json`);
fs.writeFileSync(
  manifestPath,
  `${JSON.stringify(manifest, null, 2)}\n`
);
fs.chmodSync(manifestPath, 0o644);

const preservationNotesPath = path.join(
  distributionDirectory,
  `${packageName}-PRESERVE_ON_SERVER.txt`
);
fs.copyFileSync(
  path.join(repositoryRoot, "deploy/cpanel/PRESERVE_ON_SERVER.txt"),
  preservationNotesPath
);
fs.chmodSync(preservationNotesPath, 0o644);

console.log(`Prepared: ${packageDirectory}`);
console.log(`Files: ${files.length}`);
console.log(`Manifest (not public): ${manifestPath}`);
console.log(`Deployment notes (not public): ${preservationNotesPath}`);
