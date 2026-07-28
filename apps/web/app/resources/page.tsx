import type { Metadata } from "next";
import Link from "next/link";
import { resourceArticles } from "@/data/resources";
import { site } from "@/data/site";

export const metadata: Metadata = {
  title: "Monument Dealer Learning Center",
  description:
    "Original Angel Granites guides to monument terminology, shapes, finishes, dimensions, ordering and practical dealer specifications.",
  alternates: { canonical: "/resources/" },
  openGraph: {
    title: "Monument Dealer Learning Center | Angel Granites",
    description:
      "Practical granite monument references for terminology, dimensions, finishes and ordering.",
    url: `${site.url}/resources/`,
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

export default function ResourcesPage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Angel Granites Monument Dealer Learning Center",
    url: `${site.url}/resources/`,
    mainEntity: {
      "@type": "ItemList",
      itemListElement: resourceArticles.map((article, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: article.title,
        url: `${site.url}/resources/${article.slug}/`
      }))
    }
  };

  return (
    <>
      <section className="page-hero page-hero--resources">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>Resources</span>
          </nav>
          <span className="eyebrow eyebrow--light">Dealer learning center</span>
          <h1>Practical monument knowledge for better specifications.</h1>
          <p>
            Original references to help monument dealers discuss shapes,
            finishes, dimensions, terminology, ordering and production details
            more clearly.
          </p>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell resource-grid">
          {resourceArticles.map((article, index) => (
            <Link
              className="resource-card"
              href={`/resources/${article.slug}/`}
              key={article.slug}
            >
              <span>{String(index + 1).padStart(2, "0")}</span>
              <small>{article.category}</small>
              <h2>{article.shortTitle}</h2>
              <p>{article.description}</p>
              <strong>Read the guide →</strong>
            </Link>
          ))}
        </div>
      </section>
      <section className="cta-section">
        <div className="shell cta-inner">
          <div>
            <span className="eyebrow eyebrow--light">Need order-specific help?</span>
            <h2>Use the guides, then confirm the actual specification with us.</h2>
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
    </>
  );
}
