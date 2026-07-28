import Link from "next/link";
import { site } from "@/data/site";

type LegalSection = {
  id: string;
  title: string;
  content: React.ReactNode;
};

export function LegalLayout({
  title,
  description,
  effectiveDate,
  sections
}: {
  title: string;
  description: string;
  effectiveDate: string;
  sections: LegalSection[];
}) {
  const schema = {
    "@context": "https://schema.org",
    "@type": "WebPage",
    name: title,
    description,
    publisher: {
      "@type": "Organization",
      name: site.legalName,
      url: site.url
    },
    dateModified: "2025-07-12"
  };

  return (
    <>
      <section className="page-hero page-hero--legal">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>{title}</span>
          </nav>
          <span className="eyebrow eyebrow--light">Legal information</span>
          <h1>{title}</h1>
          <p>{description}</p>
        </div>
      </section>
      <section className="section section--paper legal-page">
        <div className="shell legal-content-grid">
          <aside className="legal-toc" aria-label={`${title} contents`}>
            <span>On this page</span>
            <nav>
              {sections.map((section) => (
                <a key={section.id} href={`#${section.id}`}>
                  {section.title}
                </a>
              ))}
            </nav>
            <p>Effective {effectiveDate}</p>
          </aside>
          <article className="legal-document">
            <header>
              <span>Effective date</span>
              <strong>{effectiveDate}</strong>
            </header>
            {sections.map((section) => (
              <section id={section.id} key={section.id}>
                <h2>{section.title}</h2>
                {section.content}
              </section>
            ))}
            <div className="legal-contact">
              <strong>Questions about this document?</strong>
              <p>
                Contact Angel Stones at{" "}
                <a href={`mailto:${site.email}`}>{site.email}</a> or call{" "}
                <a href={site.phoneHref}>{site.phone}</a>.
              </p>
            </div>
          </article>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
    </>
  );
}
