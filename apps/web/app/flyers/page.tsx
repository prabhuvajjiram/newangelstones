import type { Metadata } from "next";
import Link from "next/link";
import { flyers } from "@/data/flyers";
import { site } from "@/data/site";

export const metadata: Metadata = {
  title: "Granite Monument Special Flyers",
  description:
    "View current Angel Granites monument dealer flyers for granite colors, convention promotions and in-stock special designs.",
  alternates: { canonical: "/flyers/" },
  openGraph: {
    title: "Granite Monument Special Flyers | Angel Granites",
    description:
      "Current granite color, convention and in-stock design flyers for monument dealers.",
    url: `${site.url}/flyers/`,
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

export default function FlyersPage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Angel Granites Special Flyers",
    url: `${site.url}/flyers/`,
    mainEntity: {
      "@type": "ItemList",
      itemListElement: flyers.map((flyer, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: flyer.name,
        url: `${site.url}${encodeURI(flyer.pdfPath)}`
      }))
    }
  };

  return (
    <>
      <section className="page-hero page-hero--flyers">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>Special Flyers</span>
          </nav>
          <span className="eyebrow eyebrow--light">Dealer materials</span>
          <h1>Current granite monument flyers.</h1>
          <p>
            Review current color, convention and special-design materials. Each
            flyer opens as a downloadable PDF.
          </p>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell flyer-grid">
          {flyers.map((flyer) => (
            <a
              className="flyer-card"
              href={flyer.pdfPath}
              target="_blank"
              rel="noopener noreferrer"
              key={flyer.id}
            >
              <span className="flyer-image">
                <img src={flyer.imagePath} alt={`${flyer.name} cover`} />
              </span>
              <span className="flyer-copy">
                <small>{flyer.label}</small>
                <strong>{flyer.name}</strong>
                <p>{flyer.description}</p>
                <span>Open PDF ↗</span>
              </span>
            </a>
          ))}
        </div>
      </section>
      <section className="section flyer-guide">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">Using dealer flyers</span>
            <h2>Keep the promotion connected to current availability.</h2>
            <p>
              Angel Granites flyers provide convenient color, design and
              convention references for monument dealers. Download the PDF for
              the clearest version, then verify the applicable product,
              quantity, location and timing before presenting a final quote.
            </p>
          </div>
          <div className="advantage-grid">
            <article>
              <h3>Reference the flyer name</h3>
              <p>
                Include the flyer title and pictured design or color when
                contacting our team so the intended promotion can be identified
                quickly.
              </p>
            </article>
            <article>
              <h3>Confirm the specification</h3>
              <p>
                A flyer image does not replace the final dimensions, finish
                schedule, artwork, quantity or approved production drawing.
              </p>
            </article>
            <article>
              <h3>Verify current terms</h3>
              <p>
                Availability and promotional terms may change. Ask our team to
                confirm that the referenced material remains current.
              </p>
            </article>
          </div>
          <nav className="collection-guide-links" aria-label="Flyer resources">
            <Link href="/inventory/">Search current inventory</Link>
            <Link href="/granite-colors/">Compare granite colors</Link>
            <Link href="/contact/">Confirm a flyer special</Link>
          </nav>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
    </>
  );
}
