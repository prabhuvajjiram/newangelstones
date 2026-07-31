import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { SeoBreadcrumbs } from "@/components/seo-breadcrumbs";
import { curatedDesigns, getCuratedDesign } from "@/data/designs";
import { site } from "@/data/site";

export function generateStaticParams() {
  return curatedDesigns.map((design) => ({ slug: design.slug }));
}

export async function generateMetadata({
  params
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const design = getCuratedDesign((await params).slug);
  if (!design) return {};
  const path = `/designs/${design.slug}/`;
  return {
    title: `${design.code} ${design.name}`,
    description: design.description,
    alternates: { canonical: path },
    openGraph: {
      title: `${design.code} ${design.name} | Angel Granites`,
      description: design.description,
      url: `${site.url}${path}`,
      images: [{ url: design.image, alt: `${design.code} ${design.name}` }]
    }
  };
}

export default async function DesignDetailPage({
  params
}: {
  params: Promise<{ slug: string }>;
}) {
  const design = getCuratedDesign((await params).slug);
  if (!design) notFound();
  const designIndex = curatedDesigns.findIndex(
    (item) => item.slug === design.slug
  );
  const previousDesign =
    curatedDesigns[
      (designIndex - 1 + curatedDesigns.length) % curatedDesigns.length
    ];
  const nextDesign = curatedDesigns[(designIndex + 1) % curatedDesigns.length];
  const related = curatedDesigns
    .filter((item) => item.slug !== design.slug)
    .sort(
      (left, right) =>
        Number(
          right.collectionSlug === design.collectionSlug ||
            right.productType === design.productType
        ) -
        Number(
          left.collectionSlug === design.collectionSlug ||
            left.productType === design.productType
        )
    )
    .slice(0, 3);
  const path = `/designs/${design.slug}/`;
  const pageUrl = `${site.url}${path}`;
  const imageUrl = `${site.url}${encodeURI(design.image)}`;
  const pageSchema = {
    "@context": "https://schema.org",
    "@type": "ItemPage",
    "@id": `${pageUrl}#webpage`,
    url: pageUrl,
    name: `${design.code} ${design.name}`,
    description: design.description,
    inLanguage: "en-US",
    keywords: [
      design.code,
      design.productType,
      "granite monument design",
      ...design.referenceColors
    ].join(", "),
    primaryImageOfPage: {
      "@type": "ImageObject",
      "@id": `${pageUrl}#primaryimage`,
      url: imageUrl,
      contentUrl: imageUrl,
      caption: `${design.code} ${design.name} granite design reference`
    },
    about: {
      "@type": "Thing",
      "@id": `${pageUrl}#design-reference`,
      name: `${design.code} ${design.name}`,
      description: design.description,
      identifier: design.code,
      image: imageUrl
    },
    isPartOf: {
      "@type": "WebSite",
      "@id": `${site.url}/#website`,
      name: site.name,
      url: `${site.url}/`
    },
    publisher: { "@id": `${site.url}/#organization` }
  };

  return (
    <>
      <section className="design-detail">
        <div className="shell">
          <nav className="breadcrumbs breadcrumbs--dark" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span>
            <Link href={`/${design.collectionSlug}/`}>{design.collectionName}</Link><span>/</span>
            <span>{design.code}</span>
          </nav>
          <div className="design-detail-grid">
            <figure>
              <div className="design-detail-image">
                <img
                  src={design.image}
                  alt={`${design.code} ${design.name} granite design reference`}
                />
                <nav
                  className="color-detail-image-nav"
                  aria-label="Browse curated monument designs"
                >
                  <Link
                    className="color-detail-arrow color-detail-arrow--previous"
                    href={`/designs/${previousDesign.slug}/`}
                    aria-label={`Previous design: ${previousDesign.code} ${previousDesign.name}`}
                    title={`Previous: ${previousDesign.code} ${previousDesign.name}`}
                    rel="prev"
                  >
                    <span aria-hidden="true">←</span>
                  </Link>
                  <Link
                    className="color-detail-arrow color-detail-arrow--next"
                    href={`/designs/${nextDesign.slug}/`}
                    aria-label={`Next design: ${nextDesign.code} ${nextDesign.name}`}
                    title={`Next: ${nextDesign.code} ${nextDesign.name}`}
                    rel="next"
                  >
                    <span aria-hidden="true">→</span>
                  </Link>
                </nav>
              </div>
              <figcaption>
                <span>
                  Design {designIndex + 1} of {curatedDesigns.length}
                </span>
                <span>
                  Design reference photograph; color and natural stone movement
                  vary.
                </span>
              </figcaption>
            </figure>
            <div>
              <span className="eyebrow">{design.productType} · {design.code}</span>
              <h1>{design.name}</h1>
              <p>{design.introduction}</p>
              <p className="reference-note">
                Reference configurations are shown for planning—not as a statement
                of current stock or availability.
              </p>
              <dl className="stone-specs">
                <div><dt>Colors</dt><dd>{design.referenceColors.join(", ")}</dd></div>
                <div><dt>Sizes</dt><dd>{design.referenceSizes.join("; ")}</dd></div>
                <div><dt>Finish</dt><dd>{design.referenceFinish}</dd></div>
              </dl>
              <div className="design-actions">
                <Link
                  className="button button--gold"
                  href={`/inventory/?search=${encodeURIComponent(design.code)}`}
                >
                  Search current inventory
                </Link>
                <Link
                  className="button button--outline-dark"
                  href={`/contact/?design=${encodeURIComponent(design.code)}`}
                >
                  Ask about this design
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section className="section collection-guide">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">Design planning</span>
            <h2>Details to confirm for {design.code}.</h2>
            <p>
              Use the design number when discussing the monument, then confirm
              every dimension, finish, carving note and inscription area on the
              approved order drawing.
            </p>
          </div>
          <div className="advantage-grid">
            {design.features.map((feature) => (
              <article key={feature.title}>
                <h3>{feature.title}</h3>
                <p>{feature.description}</p>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell">
          <div className="section-heading-row">
            <div className="section-heading">
              <span className="eyebrow">Continue browsing</span>
              <h2>Related granite designs</h2>
            </div>
            <Link className="text-link" href={`/${design.collectionSlug}/`}>
              View collection ↗
            </Link>
          </div>
          <div className="design-related">
            {related.map((item) => (
              <Link href={`/designs/${item.slug}/`} key={item.slug}>
                <img
                  src={item.image}
                  alt={`${item.code} ${item.name} granite monument design`}
                  loading="lazy"
                />
                <span>{item.code}</span>
                <strong>{item.name}</strong>
              </Link>
            ))}
          </div>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(pageSchema) }}
      />
      <SeoBreadcrumbs
        items={[
          { name: "Home", path: "/" },
          { name: design.collectionName, path: `/${design.collectionSlug}/` },
          { name: `${design.code} ${design.name}`, path }
        ]}
      />
    </>
  );
}
