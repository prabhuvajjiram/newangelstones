export type LiveProductImage = {
  name: string;
  src: string;
  category: string;
};

type DirectoryEntry = {
  name?: unknown;
  path?: unknown;
};

type DirectoryResponse = {
  success?: boolean;
  files?: DirectoryEntry[];
};

const supportedImage = /\.(?:gif|jpe?g|png)(?:\?v=\d+)?$/i;

function safeProductPath(value: unknown): string | null {
  if (typeof value !== "string") return null;

  const trimmed = value.trim();
  const [pathname, query = ""] = trimmed.split("?", 2);
  const normalized = pathname.replace(/^\/+/, "");
  if (
    !normalized.startsWith("images/products/") ||
    normalized.split("/").includes("..") ||
    !supportedImage.test(`${normalized}${query ? `?${query}` : ""}`)
  ) {
    return null;
  }

  const cacheBuster = /^\d+$/.test(new URLSearchParams(query).get("v") ?? "")
    ? `?v=${new URLSearchParams(query).get("v")}`
    : "";
  return `/${normalized}${cacheBuster}`;
}

function displayName(value: unknown, src: string): string {
  const filename =
    typeof value === "string" && value.trim()
      ? value.trim()
      : decodeURIComponent(src.split("?")[0].split("/").pop() ?? "");
  return filename
    .replace(/\.[^.]+$/, "")
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

async function readDirectory(
  directory: string,
  signal?: AbortSignal
): Promise<DirectoryEntry[]> {
  const parameters = new URLSearchParams({ directory });
  const response = await fetch(`/get_directory_files.php?${parameters}`, {
    headers: { Accept: "application/json" },
    cache: "no-store",
    signal
  });
  if (!response.ok) throw new Error("Product image directory is unavailable.");

  const payload = (await response.json()) as DirectoryResponse;
  if (payload.success === false || !Array.isArray(payload.files)) {
    throw new Error("Product image directory returned an unexpected response.");
  }
  return payload.files;
}

export async function fetchLiveProductImages(
  category: string,
  signal?: AbortSignal
): Promise<LiveProductImage[]> {
  const files = await readDirectory(`products/${category}`, signal);
  return files.flatMap((file) => {
    const src = safeProductPath(file.path);
    if (!src) return [];
    return [
      {
        name: displayName(file.name, src),
        src,
        category
      }
    ];
  });
}

export async function fetchAllLiveProductImages(
  signal?: AbortSignal
): Promise<LiveProductImage[]> {
  const entries = await readDirectory("products", signal);
  const categories = entries.flatMap((entry) => {
    if (typeof entry.name !== "string" || typeof entry.path !== "string") return [];
    const normalizedPath = entry.path.replace(/^\/+/, "");
    if (
      normalizedPath !== `images/products/${entry.name}` ||
      entry.name.includes("/") ||
      entry.name.includes("..")
    ) {
      return [];
    }
    return [entry.name];
  });

  const results = await Promise.allSettled(
    categories.map((category) => fetchLiveProductImages(category, signal))
  );
  return results.flatMap((result) =>
    result.status === "fulfilled" ? result.value : []
  );
}

export function productImageKey(src: string): string {
  const pathname = src.split("?")[0];
  return pathname
    .replace(/\.[^.]+$/, "")
    .replace(/^\/+/, "")
    .toLowerCase();
}
