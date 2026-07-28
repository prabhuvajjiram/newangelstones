import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { SeoBreadcrumbs } from "@/components/seo-breadcrumbs";
import { getResourceArticle, resourceArticles } from "@/data/resources";
import { site } from "@/data/site";

export function generateStaticParams() {
  return resourceArticles.map((article) => ({ slug: article.slug }));
}

export async function generateMetadata({
  params
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const article = getResourceArticle(slug);
  if (!article) return {};
  return {
    title: article.title,
    description: article.description,
    alternates: { canonical: `/resources/${article.slug}/` },
    openGraph: {
      type: "article",
      title: article.title,
      description: article.description,
      url: `${site.url}/resources/${article.slug}/`,
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

export default async function ResourceArticlePage({
  params
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const article = getResourceArticle(slug);
  if (!article) notFound();
  const relatedArticles = resourceArticles
    .filter((item) => item.slug !== article.slug)
    .slice(0, 4);

  const schema = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: article.title,
    description: article.description,
    mainEntityOfPage: `${site.url}/resources/${article.slug}/`,
    author: { "@type": "Organization", name: site.name },
    publisher: { "@type": "Organization", name: site.legalName },
    image: `${site.url}${site.socialImage}`
  };

  return (
    <>
      <section className="page-hero page-hero--resources">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span>
            <Link href="/resources/">Resources</Link><span>/</span>
            <span>{article.shortTitle}</span>
          </nav>
          <span className="eyebrow eyebrow--light">{article.category}</span>
          <h1>{article.title}</h1>
          <p>{article.introduction}</p>
        </div>
      </section>
      <article className="section resource-article">
        <div className="shell resource-article-shell">
          {article.sections.map((section) => (
            <section key={section.heading}>
              <h2>{section.heading}</h2>
              {section.introduction ? <p>{section.introduction}</p> : null}
              <dl>
                {section.items.map((item) => (
                  <div key={item.title}>
                    <dt>{item.title}</dt>
                    <dd>{item.description}</dd>
                  </div>
                ))}
              </dl>
            </section>
          ))}
          <aside className="resource-note">
            <strong>Specification note</strong>
            <p>
              This guide is educational. Confirm cemetery rules, dimensions,
              finish codes, artwork and production details on the approved order
              and drawing.
            </p>
            <Link className="text-link" href="/contact/">
              Ask Angel Granites about a specification ↗
            </Link>
          </aside>
          <section className="resource-related">
            <h2>Continue with related dealer guides</h2>
            <nav
              className="collection-guide-links"
              aria-label="Related monument guides"
            >
              {relatedArticles.map((related) => (
                <Link
                  href={`/resources/${related.slug}/`}
                  key={related.slug}
                >
                  {related.shortTitle}
                </Link>
              ))}
              <Link href="/inventory/">Search current inventory</Link>
            </nav>
          </section>
        </div>
      </article>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
      <SeoBreadcrumbs
        items={[
          { name: "Home", path: "/" },
          { name: "Resources", path: "/resources/" },
          {
            name: article.shortTitle,
            path: `/resources/${article.slug}/`
          }
        ]}
      />
    </>
  );
}
