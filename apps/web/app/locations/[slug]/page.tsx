import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { SeoBreadcrumbs } from "@/components/seo-breadcrumbs";
import { locations } from "@/data/business";
import { site } from "@/data/site";

export function generateStaticParams() {
  return locations.map((location) => ({ slug: location.slug }));
}

export async function generateMetadata({
  params
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const location = locations.find((item) => item.slug === slug);
  if (!location) return {};
  return {
    title: `Wholesale Granite Monuments in ${location.name}`,
    description: location.description,
    alternates: { canonical: `/locations/${location.slug}/` },
    openGraph: {
      title: `Angel Granites — ${location.name}`,
      description: location.description,
      url: `${site.url}/locations/${location.slug}/`,
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
}

export default async function LocationPage({
  params
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const location = locations.find((item) => item.slug === slug);
  if (!location) notFound();
  const isElberton = location.slug === "elberton-ga";

  const schema = {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    name: `${site.name} — ${location.name}`,
    description: location.description,
    url: `${site.url}/locations/${location.slug}/`,
    telephone: site.phone,
    email: site.email,
    contactPoint: [
      {
        "@type": "ContactPoint",
        telephone: site.phone,
        contactType: "sales and customer service",
        areaServed: "US"
      },
      ...(isElberton
        ? [
            {
              "@type": "ContactPoint",
              telephone: site.alternateOfficePhone,
              contactType: "Elberton office",
              areaServed: "US"
            }
          ]
        : [])
    ],
    areaServed: location.serviceArea,
    parentOrganization: {
      "@type": "Organization",
      name: site.name,
      url: site.url
    },
    address: {
      "@type": "PostalAddress",
      ...location.schemaAddress,
      addressCountry: "US"
    }
  };

  return (
    <>
      <section className="page-hero page-hero--location">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span>
            <Link href="/locations/">Locations</Link><span>/</span>
            <span>{location.name}</span>
          </nav>
          <span className="eyebrow eyebrow--light">{location.eyebrow}</span>
          <h1>Wholesale granite monument support in {location.name}.</h1>
          <p>{location.description}</p>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell location-detail">
          <div>
            <span className="eyebrow">Angel Granites in {location.shortName}</span>
            <h2>Local industry presence, connected wholesale service.</h2>
            <p>{location.regionCopy}</p>
            <p>
              Dealers can contact our team to confirm current inventory,
              fabrication options, finishes, quantity, packing requirements and
              delivery planning. Please call before visiting a facility.
            </p>
          </div>
          <aside>
            <small>{location.eyebrow}</small>
            <h2>{location.name}</h2>
            <address>
              {location.addressLines.map((address) => (
                <span key={address}>{address}</span>
              ))}
            </address>
            <a href={site.phoneHref}>{site.phone}</a>
            {isElberton ? (
              <a href={site.alternateOfficePhoneHref}>
                Alternative office: {site.alternateOfficePhone}
              </a>
            ) : null}
            <a href={`mailto:${site.email}`}>{site.email}</a>
          </aside>
        </div>
      </section>
      <section className="section location-capabilities">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">
              Wholesale support from {location.shortName}
            </span>
            <h2>What dealers can coordinate through this location.</h2>
            <p>{location.serviceArea}.</p>
          </div>
          <div className="advantage-grid">
            {location.capabilities.map((capability) => (
              <article key={capability.title}>
                <h3>{capability.title}</h3>
                <p>{capability.description}</p>
              </article>
            ))}
          </div>
          <nav className="collection-guide-links" aria-label={`${location.shortName} resources`}>
            <Link href="/inventory/">Search current inventory</Link>
            <Link href="/products-services/">Review products and services</Link>
            <Link href="/resources/granite-monument-ordering-checklist/">
              Prepare an order inquiry
            </Link>
          </nav>
        </div>
      </section>
      <section className="cta-section">
        <div className="shell cta-inner">
          <div>
            <span className="eyebrow eyebrow--light">Dealer availability</span>
            <h2>Ask what is available through {location.shortName}.</h2>
          </div>
          <Link className="button button--cream" href="/contact/">
            Contact our team
          </Link>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
      <SeoBreadcrumbs
        items={[
          { name: "Home", path: "/" },
          { name: "Locations", path: "/locations/" },
          {
            name: location.name,
            path: `/locations/${location.slug}/`
          }
        ]}
      />
    </>
  );
}
