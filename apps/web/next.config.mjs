/** @type {import("next").NextConfig} */
const isStaticExport = process.env.NEXT_OUTPUT === "export";
const configuredBasePath = process.env.NEXT_PUBLIC_BASE_PATH?.trim() ?? "";
const basePath =
  configuredBasePath && configuredBasePath !== "/"
    ? `/${configuredBasePath.replace(/^\/+|\/+$/g, "")}`
    : "";

const nextConfig = {
  output: isStaticExport ? "export" : undefined,
  basePath,
  trailingSlash: true,
  images: {
    unoptimized: true
  }
};

export default nextConfig;
