export type LiveColorImage = {
  name: string;
  src: string;
};

type ColorDirectoryResponse = {
  success?: boolean;
  colors?: Array<{
    name?: unknown;
    path?: unknown;
  }>;
};

const supportedImage = /\.(?:jpe?g|png|webp)$/i;

function safeColorPath(value: unknown): string | null {
  if (typeof value !== "string") return null;
  const pathname = value.trim().split("?")[0].replace(/^\/+/, "");
  if (
    !pathname.startsWith("images/colors/") ||
    pathname.split("/").includes("..") ||
    !supportedImage.test(pathname)
  ) {
    return null;
  }
  return `/${pathname}`;
}

export async function fetchLiveColorImages(
  signal?: AbortSignal
): Promise<LiveColorImage[]> {
  const response = await fetch("/get_color_images.php", {
    headers: { Accept: "application/json" },
    signal
  });
  if (!response.ok) throw new Error("Color image directory is unavailable.");

  const payload = (await response.json()) as ColorDirectoryResponse;
  if (payload.success === false || !Array.isArray(payload.colors)) {
    throw new Error("Color image directory returned an unexpected response.");
  }

  return payload.colors.flatMap((color) => {
    const src = safeColorPath(color.path);
    if (!src || typeof color.name !== "string" || !color.name.trim()) return [];
    return [{ name: color.name.trim(), src }];
  });
}
