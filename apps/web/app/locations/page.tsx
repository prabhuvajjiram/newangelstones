import type { Metadata } from "next";
import Link from "next/link";
import { locations } from "@/data/business";
import { site } from "@/data/site";

export const metadata: Metadata = {
  title: "Angel Granites Locations",
  description:
    "Angel Granites monument production, inventory and dealer support locations in Elberton, Georgia and Barre, Vermont.",
  alternates: { canonical: "/locations/" },
  openGraph: {
    title: "Angel Granites Locations",
    description:
      "Monument production, inventory and dealer support in Elberton, Georgia and Barre, Vermont.",
    url: `${site.url}/locations/`,
    images: [
      {
        url: site.socialImage,
        width: 1200,
        height: 675,
        alt: site.socialImageAlt
      }
    ]
  }
};

export default function LocationsPage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Angel Granites Locations",
    url: `${site.url}/locations/`,
    mainEntity: {
      "@type": "ItemList",
      itemListElement: locations.map((location, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: location.name,
        url: `${site.url}/locations/${location.slug}/`
      }))
    }
  };

  return (
    <>
      <section className="page-hero">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>Locations</span>
          </nav>
          <span className="eyebrow eyebrow--light">Elberton and Barre</span>
          <h1>Granite-industry locations serving monument dealers.</h1>
          <p>
            Production, inventory and dealer support connected across two of
            America&apos;s best-known granite communities.
          </p>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell location-grid">
          {locations.map((location) => (
            <article className="location-card" key={location.slug}>
              <span className="eyebrow">{location.eyebrow}</span>
              <h2>{location.name}</h2>
              <p>{location.description}</p>
              <address>
                {location.addressLines.map((address) => (
                  <span key={address}>{address}</span>
                ))}
              </address>
              <Link className="text-link" href={`/locations/${location.slug}/`}>
                Explore {location.shortName} ↗
              </Link>
            </article>
          ))}
        </div>
      </section>
      <section className="cta-section">
        <div className="shell cta-inner">
          <div>
            <span className="eyebrow eyebrow--light">Contact and ordering</span>
            <h2>Confirm the right inventory and fulfillment location.</h2>
          </div>
          <a className="button button--cream" href={site.phoneHref}>
            Call {site.phone}
          </a>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
    </>
  );
}
