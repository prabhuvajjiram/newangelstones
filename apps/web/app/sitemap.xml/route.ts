import { collections, graniteColors } from "@/data/catalog";
import { locations } from "@/data/business";
import { curatedDesigns } from "@/data/designs";
import { resourceArticles } from "@/data/resources";
import { site } from "@/data/site";

export const dynamic = "force-static";
const contentLastModified = "2026-07-27";

function escapeXml(value: string): string {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&apos;");
}

export function GET() {
  const staticPages = [
    { path: "/", priority: "1.0", frequency: "weekly" },
    { path: "/inventory/", priority: "0.9", frequency: "daily" },
    { path: "/granite-colors/", priority: "0.9", frequency: "weekly" },
    { path: "/products-services/", priority: "0.9", frequency: "monthly" },
    { path: "/flyers/", priority: "0.8", frequency: "weekly" },
    { path: "/locations/", priority: "0.8", frequency: "monthly" },
    ...locations.map((location) => ({
      path: `/locations/${location.slug}/`,
      priority: "0.8",
      frequency: "monthly"
    })),
    { path: "/resources/", priority: "0.8", frequency: "weekly" },
    ...resourceArticles.map((article) => ({
      path: `/resources/${article.slug}/`,
      priority: "0.8",
      frequency: "monthly"
    })),
    { path: "/contact/", priority: "0.8", frequency: "monthly" },
    ...collections.map((collection) => ({
      path: `/${collection.slug}/`,
      priority: "0.9",
      frequency: "weekly"
    })),
    { path: "/privacy-policy/", priority: "0.5", frequency: "monthly" },
    { path: "/terms-of-service/", priority: "0.5", frequency: "monthly" },
    { path: "/sms-terms/", priority: "0.5", frequency: "monthly" }
  ];

  const pageXml = staticPages
    .map(
      (page) => `<url>
  <loc>${escapeXml(`${site.url}${page.path}`)}</loc>
  <lastmod>${contentLastModified}</lastmod>
  <changefreq>${page.frequency}</changefreq>
  <priority>${page.priority}</priority>
</url>`
    )
    .join("\n");

  const colorXml = graniteColors
    .map(
      (color) => `<url>
  <loc>${escapeXml(`${site.url}/colors/${color.slug}/`)}</loc>
  <lastmod>${contentLastModified}</lastmod>
  <changefreq>monthly</changefreq>
  <priority>0.7</priority>
  <image:image>
    <image:loc>${escapeXml(`${site.url}${encodeURI(color.image)}`)}</image:loc>
    <image:title>${escapeXml(`${color.name} - Monument Granite Color`)}</image:title>
    <image:caption>${escapeXml(color.description)}</image:caption>
  </image:image>
</url>`
    )
    .join("\n");

  const designXml = curatedDesigns
    .map(
      (design) => `<url>
  <loc>${escapeXml(`${site.url}/designs/${design.slug}/`)}</loc>
  <lastmod>${contentLastModified}</lastmod>
  <changefreq>monthly</changefreq>
  <priority>0.8</priority>
  <image:image>
    <image:loc>${escapeXml(`${site.url}${encodeURI(design.image)}`)}</image:loc>
    <image:title>${escapeXml(`${design.code} ${design.name}`)}</image:title>
    <image:caption>${escapeXml(design.description)}</image:caption>
  </image:image>
</url>`
    )
    .join("\n");

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
${pageXml}
${colorXml}
${designXml}
</urlset>`;

  return new Response(xml, {
    headers: { "Content-Type": "application/xml; charset=utf-8" }
  });
}
