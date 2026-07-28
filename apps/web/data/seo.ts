import type { Metadata } from "next";
import { site } from "@/data/site";

export function pageOpenGraph(
  title: string,
  description: string,
  path: string
): NonNullable<Metadata["openGraph"]> {
  return {
    type: "website",
    siteName: site.name,
    title,
    description,
    url: new URL(path, site.url).toString(),
    locale: "en_US",
    images: [
      {
        url: site.socialImage,
        width: 1200,
        height: 675,
        alt: site.socialImageAlt
      }
    ]
  };
}
