import type { Metadata } from "next";
import Link from "next/link";
import { dealerAdvantages, services } from "@/data/business";
import { site } from "@/data/site";

export const metadata: Metadata = {
  title: "Granite Monument Products & Services",
  description:
    "Wholesale granite monuments, custom fabrication, sandblasting, etching, dealer inventory and nationwide freight support from Angel Granites.",
  alternates: { canonical: "/products-services/" },
  openGraph: {
    title: "Granite Monument Products & Services | Angel Granites",
    description:
      "Granite monument supply, custom fabrication, sandblasting and etching for monument dealers and memorial professionals.",
    url: `${site.url}/products-services/`,
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

export default function ProductsServicesPage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "Service",
    provider: {
      "@type": "Organization",
      name: site.name,
      url: site.url
    },
    areaServed: "United States",
    audience: {
      "@type": "BusinessAudience",
      audienceType: "Monument dealers, memorial companies and funeral professionals"
    },
    hasOfferCatalog: {
      "@type": "OfferCatalog",
      name: "Granite monument products and services",
      itemListElement: services.map((service) => ({
        "@type": "Offer",
        itemOffered: {
          "@type": "Service",
          name: service.title,
          description: service.description
        }
      }))
    }
  };

  return (
    <>
      <section className="page-hero page-hero--services">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>Products & Services</span>
          </nav>
          <span className="eyebrow eyebrow--light">Wholesale monument supply</span>
          <h1>Granite products and finishing services.</h1>
          <p>
            Work with one team for monument granite, custom fabrication,
            sandblasting, etching, inventory confirmation and shipment planning.
          </p>
        </div>
      </section>

      <section className="section section--paper">
        <div className="shell">
          <div className="section-heading-row">
            <div className="section-heading">
              <span className="eyebrow">Products and services</span>
              <h2>From stone selection to finished memorial work.</h2>
              <p>
                Angel Granites serves monument dealers, memorial companies,
                funeral professionals and wholesale buyers rather than operating
                as a consumer ecommerce store.
              </p>
            </div>
          </div>
          <div className="service-grid">
            {services.map((service, index) => (
              <article className="service-card" key={service.title}>
                <span>{String(index + 1).padStart(2, "0")}</span>
                <h2>{service.title}</h2>
                <p>{service.description}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <div className="section-heading-row">
            <div className="section-heading">
              <span className="eyebrow">Dealer support</span>
              <h2>Built around the way monument dealers order.</h2>
              <p>
                Availability, dimensions, granite color, finish, artwork and
                freight requirements stay connected throughout the quote.
              </p>
            </div>
            <Link className="text-link" href="/inventory/">
              Search current inventory ↗
            </Link>
          </div>
          <div className="advantage-grid">
            {dealerAdvantages.map((advantage) => (
              <article key={advantage.title}>
                <h3>{advantage.title}</h3>
                <p>{advantage.description}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="cta-section">
        <div className="shell cta-inner">
          <div>
            <span className="eyebrow eyebrow--light">Request a dealer quote</span>
            <h2>Send the design, stone, dimensions and delivery location.</h2>
          </div>
          <Link className="button button--cream" href="/contact/">
            Talk with our team
          </Link>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
    </>
  );
}
