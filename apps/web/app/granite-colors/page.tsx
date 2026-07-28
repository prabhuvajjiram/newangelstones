import type { Metadata } from "next";
import Link from "next/link";
import { preload } from "react-dom";
import { ColorGrid } from "@/components/color-grid";
import {
  graniteColors,
  type GraniteColorFamily
} from "@/data/catalog";
import { pageOpenGraph } from "@/data/seo";
import { site } from "@/data/site";

const description =
  "Compare black, gray, blue, green, red and multicolor granite monument colors. View polished headstone color samples and check current inventory.";
export const metadata: Metadata = {
  title: "Granite Monument & Headstone Colors",
  description,
  alternates: { canonical: "/granite-colors/" },
  openGraph: pageOpenGraph(
    "Granite Colors for Monuments | Angel Granites",
    description,
    "/granite-colors/"
  )
};

const familyOrder: GraniteColorFamily[] = [
  "Black",
  "Gray",
  "Blue",
  "Green",
  "Red & Pink",
  "Brown & Multicolor"
];

const familyCopy: Record<GraniteColorFamily, string> = {
  Black:
    "Compare deep black monument granites where polished surfaces can create strong visual contrast for lettering and artwork.",
  Gray:
    "Explore light, medium and darker gray memorial granites, including Barre and Georgia color references in the catalog.",
  Blue:
    "Review blue granite headstone colors ranging from subtle blue-gray movement to more pronounced blue mineral patterns.",
  Green:
    "Compare green memorial granite samples with different grain, movement and tonal character.",
  "Red & Pink":
    "Explore red and pink granite monument colors when a warmer stone palette is part of the memorial design.",
  "Brown & Multicolor":
    "Review multicolor granite samples whose movement can combine several visible tones within one natural stone surface."
};

const faqs = [
  {
    question: "What granite colors are used for headstones and monuments?",
    answer:
      "Monument granite is available in black, gray, blue, green, red, pink and multicolor selections. The right choice depends on the design, desired lettering contrast, finish, cemetery requirements and current material availability."
  },
  {
    question: "Will the finished monument match the online granite sample exactly?",
    answer:
      "No online photograph can represent every block exactly. Granite is natural stone, so grain, movement and tone vary. Lighting, photography and polished or textured finishes also change how a color appears."
  },
  {
    question: "Can the tablet, base and vase be matched in the same granite color?",
    answer:
      "They can be specified together, but close matching should be requested when the order includes several components or an addition to an existing memorial. Confirm the selected material and finish for every component."
  },
  {
    question: "How do I find monuments currently available in a color?",
    answer:
      "Open a color detail page and use its inventory link. The inventory search checks current product records, while the color gallery remains a planning catalog and does not promise live stock."
  }
];

export default function GraniteColorsPage() {
  preload("/images/colors/Green Wave Quartzite.webp", {
    as: "image",
    fetchPriority: "high"
  });

  const grouped = familyOrder.map((family) => ({
    family,
    colors: graniteColors.filter((color) => color.family === family)
  }));
  const collectionSchema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Granite Monument and Headstone Colors",
    description,
    url: `${site.url}/granite-colors/`,
    mainEntity: {
      "@type": "ItemList",
      numberOfItems: graniteColors.length,
      itemListElement: graniteColors.map((color, index) => ({
        "@type": "ListItem",
        position: index + 1,
        name: color.name,
        url: `${site.url}/colors/${color.slug}/`,
        image: `${site.url}${encodeURI(color.image)}`
      }))
    }
  };
  const faqSchema = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: { "@type": "Answer", text: faq.answer }
    }))
  };

  return (
    <>
      <section className="page-hero page-hero--colors">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/" prefetch={false}>Home</Link><span>/</span><span>Granite Colors</span>
          </nav>
          <span className="eyebrow eyebrow--light">Natural stone palette</span>
          <h1>Granite colors for lasting memorials</h1>
          <p>
            Compare {graniteColors.length} natural-stone monument and headstone
            color samples across black, gray, blue, green, red, pink and
            multicolor families. Every block is natural and will vary, so use
            these samples to narrow the selection and then confirm current
            material or monument inventory.
          </p>
        </div>
      </section>
      <section className="section catalog-section">
        <div className="shell">
          <div className="section-heading color-catalog-heading">
            <span className="eyebrow">Polished stone samples</span>
            <h2>Browse all granite colors.</h2>
            <p>
              Search by a color name or family. Each sample opens a dedicated page
              with specification guidance and a direct current-inventory search.
            </p>
          </div>
          <ColorGrid colors={graniteColors} />
        </div>
      </section>
      <section className="section color-family-section">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">Compare by color family</span>
            <h2>Find a memorial granite color.</h2>
            <p>
              Start with the visual family a customer is searching for, then compare
              individual polished samples, natural movement and the finish planned
              for the monument.
            </p>
          </div>
          <div className="color-family-grid">
            {grouped.map(({ family, colors }) => (
              <article id={`family-${family.toLowerCase().replace(/[^a-z]+/g, "-")}`} key={family}>
                <span>{colors.length} {colors.length === 1 ? "color" : "colors"}</span>
                <h2>{family} granite monument colors</h2>
                <p>{familyCopy[family]}</p>
                <nav aria-label={`${family} granite colors`}>
                  {colors.map((color) => (
                    <Link href={`/colors/${color.slug}/`} key={color.slug}>
                      {color.name}
                    </Link>
                  ))}
                </nav>
              </article>
            ))}
          </div>
        </div>
      </section>
      <section className="section color-selection-guide">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">Color selection guide</span>
            <h2>Compare more than the color name.</h2>
          </div>
          <div className="advantage-grid">
            <article>
              <h3>Lettering contrast</h3>
              <p>
                Review how lettering, carving and artwork will read against both
                polished and textured areas of the selected granite.
              </p>
            </article>
            <article>
              <h3>Natural variation</h3>
              <p>
                Grain, veining, mineral movement and overall tone vary by block.
                Use physical or current-material references when a close match is
                important.
              </p>
            </article>
            <article>
              <h3>Complete assemblies</h3>
              <p>
                Specify the tablet, base, vases and accessories together, including
                the finish required on every face, top, end and margin.
              </p>
            </article>
          </div>
          <nav className="collection-guide-links" aria-label="Granite color planning resources">
            <Link href="/inventory/">Search current granite inventory</Link>
            <Link href="/resources/granite-monument-finishes/">Compare monument finishes</Link>
            <Link href="/resources/granite-monument-ordering-checklist/">Use the ordering checklist</Link>
          </nav>
        </div>
      </section>
      <section className="section section--paper color-faq">
        <div className="shell resource-article-shell">
          <div className="section-heading">
            <span className="eyebrow">Granite color questions</span>
            <h2>Choosing a headstone or monument color.</h2>
          </div>
          <div className="color-faq-list">
            {faqs.map((faq) => (
              <article key={faq.question}>
                <h3>{faq.question}</h3>
                <p>{faq.answer}</p>
              </article>
            ))}
          </div>
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(collectionSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(faqSchema) }}
      />
    </>
  );
}
