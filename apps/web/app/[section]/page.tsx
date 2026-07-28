import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { CatalogGrid } from "@/components/catalog-grid";
import { SeoBreadcrumbs } from "@/components/seo-breadcrumbs";
import { SectionHeading } from "@/components/section-heading";
import { collections, getCollection } from "@/data/catalog";
import { site } from "@/data/site";

export function generateStaticParams() {
  return collections.map((collection) => ({ section: collection.slug }));
}

export async function generateMetadata({
  params
}: {
  params: Promise<{ section: string }>;
}): Promise<Metadata> {
  const { section } = await params;
  const collection = getCollection(section);
  if (!collection) return {};
  const hero = collection.images[0]?.src;
  return {
    title: collection.name,
    description: collection.description,
    alternates: { canonical: `/${collection.slug}/` },
    openGraph: {
      title: collection.name,
      description: collection.description,
      url: `${site.url}/${collection.slug}/`,
      images: hero ? [hero] : []
    }
  };
}

export default async function CollectionPage({
  params
}: {
  params: Promise<{ section: string }>;
}) {
  const { section } = await params;
  const collection = getCollection(section);
  if (!collection) notFound();

  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: collection.name,
    description: collection.description,
    url: `${site.url}/${collection.slug}/`,
    mainEntity: {
      "@type": "ItemList",
      numberOfItems: collection.images.length,
      itemListElement: collection.images.slice(0, 50).map((image, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: image.name,
        image: `${site.url}${encodeURI(image.src)}`
      }))
    }
  };

  return (
    <>
      <section className="page-hero page-hero--catalog">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link>
            <span>/</span>
            <span>{collection.name}</span>
          </nav>
          <span className="eyebrow eyebrow--light">{collection.eyebrow}</span>
          <h1>{collection.name}</h1>
          <p>{collection.description}</p>
        </div>
      </section>
      <section className="section catalog-section">
        <div className="shell">
          <CatalogGrid
            images={collection.images}
            collectionName={collection.name}
            directory={collection.directory}
          />
          <div className="availability-note">
            <div>
              <strong>Need current availability or a different color?</strong>
              <p>
                Send the design number, required dimensions and delivery location.
                Our team will confirm stock or production options.
              </p>
            </div>
            <Link className="button button--ink" href="/contact/">
              Request availability
            </Link>
          </div>
        </div>
      </section>
      <section className="section section--paper collection-guide">
        <div className="shell">
          <SectionHeading
            eyebrow="Dealer specification guide"
            title={collection.guide.title}
            description={collection.guide.introduction}
          />
          <div className="advantage-grid">
            {collection.guide.items.map((item) => (
              <article key={item.title}>
                <h3>{item.title}</h3>
                <p>{item.description}</p>
              </article>
            ))}
          </div>
          <nav className="collection-guide-links" aria-label="Related dealer resources">
            <Link href="/inventory/">Search current inventory</Link>
            <Link href="/granite-colors/">Compare granite colors</Link>
            <Link href="/resources/how-to-read-monument-dimensions/">
              Read monument dimensions
            </Link>
            <Link href="/products-services/">Review production services</Link>
          </nav>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
      <SeoBreadcrumbs
        items={[
          { name: "Home", path: "/" },
          { name: collection.name, path: `/${collection.slug}/` }
        ]}
      />
    </>
  );
}
