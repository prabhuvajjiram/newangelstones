import type { MetadataRoute } from "next";

export const dynamic = "force-static";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "Angel Granites",
    short_name: "Angel Granites",
    description: "Wholesale granite monument supply and product catalog.",
    start_url: "/",
    display: "standalone",
    background_color: "#faf8f2",
    theme_color: "#173f35",
    icons: [
      {
        src: "/android-icon-192x192.png",
        sizes: "192x192",
        type: "image/png"
      }
    ]
  };
}
