import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ColorDetailClose } from "@/components/color-detail-close";
import { SeoBreadcrumbs } from "@/components/seo-breadcrumbs";
import { getColor, graniteColors } from "@/data/catalog";
import { curatedDesigns } from "@/data/designs";
import { site } from "@/data/site";

export function generateStaticParams() {
  return graniteColors.map((color) => ({ slug: color.slug }));
}

export async function generateMetadata({
  params
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const color = getColor(slug);
  if (!color) return {};
  const isSilkBlue = color.slug === "blue-silk-granite";
  const title = isSilkBlue
    ? "Silk Blue Granite for Monuments & Headstones"
    : `${color.name} Monument Stone`;
  const description = isSilkBlue
    ? "Silk Blue (Blue Silk) granite for wholesale monuments and headstones. Compare the polished sample, monument uses, finishes and current U.S. inventory."
    : color.description;
  return {
    title,
    description,
    keywords: isSilkBlue
      ? [
          "Silk Blue Granite",
          "Blue Silk Granite",
          "Silk Blue granite monument",
          "Silk Blue granite headstone"
        ]
      : undefined,
    alternates: { canonical: `/colors/${color.slug}/` },
    openGraph: {
      title: isSilkBlue
        ? "Silk Blue Granite for Monuments | Angel Granites"
        : `${color.name} | Angel Granites`,
      description,
      url: `${site.url}/colors/${color.slug}/`,
      images: [
        {
          url: color.image,
          alt: isSilkBlue
            ? "Silk Blue Granite, also called Blue Silk Granite, polished monument stone sample"
            : `${color.name} polished monument stone sample`
        }
      ]
    }
  };
}

export default async function ColorDetailPage({
  params
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const color = getColor(slug);
  if (!color) notFound();
  const isSilkBlue = color.slug === "blue-silk-granite";
  const colorIndex = graniteColors.findIndex((item) => item.slug === color.slug);
  const previousColor =
    graniteColors[
      (colorIndex - 1 + graniteColors.length) % graniteColors.length
    ];
  const nextColor = graniteColors[(colorIndex + 1) % graniteColors.length];
  const related = graniteColors
    .filter((item) => item.slug !== color.slug && item.family === color.family)
    .slice(0, 4);
  const relatedDesigns = curatedDesigns
    .filter((design) =>
      design.referenceColors.some(
        (reference) =>
          reference.toLowerCase() ===
          color.name
            .replace(/ (?:granite|quartzite|marble|sandstone)$/i, "")
            .toLowerCase()
      )
    )
    .slice(0, 3);
  const familyGuidance = ({
    Black:
      "Black granite is often compared for the visual contrast it can provide between polished stone, lettering and carved or frosted treatments.",
    Gray:
      "Gray granite ranges from lighter to darker tones, so compare the sample and planned finish under the lighting where the memorial will be viewed.",
    Blue:
      "Blue granite can show subtle blue-gray grain or stronger blue mineral movement; review the current stone when pattern is important.",
    Green:
      "Green granite selections vary widely in tone and movement, making a current-material review especially useful before approval.",
    "Red & Pink":
      "Red and pink granite create a warmer monument palette; compare the stone with lettering, carving and companion components before ordering.",
    "Brown & Multicolor":
      "Multicolor granite can combine several visible tones and directional movement, so component orientation and close matching should be discussed."
  }[color.family]).replace(
    /\bgranite\b/gi,
    color.material.toLowerCase()
  );
  const pageUrl = `${site.url}/colors/${color.slug}/`;
  const imageUrl = `${site.url}${encodeURI(color.image)}`;
  const schema = {
    "@context": "https://schema.org",
    "@type": "ItemPage",
    "@id": `${pageUrl}#webpage`,
    url: pageUrl,
    name: `${color.name} Monument Stone`,
    description: color.description,
    inLanguage: "en-US",
    keywords: [
      color.name,
      ...(isSilkBlue ? ["Blue Silk Granite"] : []),
      `${color.family} monument stone`,
      `${color.material} headstone color`,
      "wholesale monument granite"
    ].join(", "),
    primaryImageOfPage: {
      "@type": "ImageObject",
      "@id": `${pageUrl}#primaryimage`,
      url: imageUrl,
      contentUrl: imageUrl,
      caption: `${color.name} polished natural-stone color sample`
    },
    about: {
      "@type": "Thing",
      "@id": `${pageUrl}#stone-color`,
      name: color.name,
      alternateName: isSilkBlue ? "Blue Silk Granite" : undefined,
      description: color.description,
      identifier: color.sku,
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
      <section className="color-detail">
        <div className="shell">
          <nav className="breadcrumbs breadcrumbs--dark" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span>
            <Link href="/granite-colors/">Granite Colors</Link><span>/</span>
            <span>{color.name}</span>
          </nav>
          <div className="color-detail-grid">
            <figure>
              <div className="color-detail-image">
                <img
                  src={color.image}
                  alt={`${color.name} polished stone sample`}
                />
                <ColorDetailClose />
                <nav
                  className="color-detail-image-nav"
                  aria-label="Browse granite colors"
                >
                  <Link
                    className="color-detail-arrow color-detail-arrow--previous"
                    href={`/colors/${previousColor.slug}/`}
                    aria-label={`Previous color: ${previousColor.name}`}
                    title={`Previous: ${previousColor.name}`}
                    rel="prev"
                  >
                    <span aria-hidden="true">←</span>
                  </Link>
                  <Link
                    className="color-detail-arrow color-detail-arrow--next"
                    href={`/colors/${nextColor.slug}/`}
                    aria-label={`Next color: ${nextColor.name}`}
                    title={`Next: ${nextColor.name}`}
                    rel="next"
                  >
                    <span aria-hidden="true">→</span>
                  </Link>
                </nav>
              </div>
              <figcaption>
                <span>
                  Color {colorIndex + 1} of {graniteColors.length}
                </span>
                <span>Natural stone varies by block and lighting.</span>
              </figcaption>
            </figure>
            <div>
              <span className="eyebrow">{color.material} color · {color.sku}</span>
              <h1>{color.name}</h1>
              <p>{color.description}</p>
              <dl className="stone-specs">
                <div><dt>Material</dt><dd>Natural {color.material.toLowerCase()}</dd></div>
                <div><dt>Color family</dt><dd>{color.family}</dd></div>
                {isSilkBlue ? (
                  <div><dt>Also known as</dt><dd>Blue Silk Granite</dd></div>
                ) : null}
                <div><dt>Typical use</dt><dd>Monuments, bases and memorials</dd></div>
                <div><dt>Availability</dt><dd>Confirm with our sales team</dd></div>
              </dl>
              <div className="design-actions">
                <Link
                  className="button button--gold"
                  href={`/inventory/?search=${encodeURIComponent(color.name)}`}
                >
                  Search inventory by color
                </Link>
                <Link
                  className="button button--outline-dark"
                  href={`/contact/?color=${encodeURIComponent(color.name)}`}
                >
                  Ask about this color
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section className="section section--paper">
        <div className="shell">
          <div className="section-heading-row">
            <div className="section-heading">
              <span className="eyebrow">Continue comparing</span>
              <h2>Related natural-stone colors</h2>
            </div>
            <Link className="text-link" href="/granite-colors/">View all colors ↗</Link>
          </div>
          <div className="related-colors">
            {related.map((item) => (
              <Link href={`/colors/${item.slug}/`} key={item.slug}>
                <img
                  src={item.image}
                  alt={`${item.name} polished stone color sample`}
                  loading="lazy"
                />
                <strong>{item.name}</strong>
              </Link>
            ))}
          </div>
        </div>
      </section>
      <section className="section color-order-guide">
        <div className="shell">
          <div className="section-heading">
            <span className="eyebrow">Stone specification</span>
            <h2>{color.name} for headstones and monuments.</h2>
            <p>
              {isSilkBlue ? (
                <>
                  Silk Blue Granite—also searched as Blue Silk Granite—shows a
                  dark blue-gray field with flowing lighter movement. Monument
                  dealers use this color for headstones, tablets, bases and
                  coordinated memorial components. Compare the current stone and
                  planned finish before approving a close match.
                </>
              ) : (
                <>
                  {familyGuidance} Use this polished sample to compare the general
                  character of {color.name}, then connect the color choice to the
                  monument design, component dimensions, finish schedule and
                  current material availability.
                </>
              )}
            </p>
          </div>
          <div className="advantage-grid">
            <article>
              <h3>
                {isSilkBlue
                  ? "Compare Silk Blue movement"
                  : "Expect natural variation"}
              </h3>
              <p>
                {isSilkBlue
                  ? "The blue-gray tone and lighter flowing pattern vary naturally by block. Review the current material when movement, orientation or component matching is important."
                  : "Natural stone varies. Grain, movement and tone can differ between blocks and may appear different under showroom, outdoor and photographic lighting."}
              </p>
            </article>
            <article>
              <h3>Review the finish</h3>
              <p>
                Polished, honed, steeled, dusted and rock-pitched surfaces
                reflect light differently. Confirm the intended finish on each
                face, top, end and margin.
              </p>
            </article>
            <article>
              <h3>Coordinate every component</h3>
              <p>
                Tell us when the die, base, vases or other pieces must be
                matched closely, especially for additions or multi-component
                memorial assemblies.
              </p>
            </article>
          </div>
          <nav className="collection-guide-links" aria-label={`${color.name} resources`}>
            <Link href="/resources/granite-monument-finishes/">
              Compare monument finishes
            </Link>
            <Link href={`/inventory/?search=${encodeURIComponent(color.name)}`}>
              Search {color.name} inventory
            </Link>
            <Link href="/monuments/">Browse monument designs</Link>
            <Link href="/contact/">Confirm color availability</Link>
          </nav>
        </div>
      </section>
      {relatedDesigns.length ? (
        <section className="section section--paper">
          <div className="shell">
            <div className="section-heading-row">
              <div className="section-heading">
                <span className="eyebrow">Design references</span>
                <h2>Granite designs recorded in {color.name}</h2>
                <p>
                  These design records include {color.name} as a reference color.
                  Use the inventory search to confirm what is available now.
                </p>
              </div>
              <Link
                className="text-link"
                href={`/inventory/?search=${encodeURIComponent(color.name)}`}
              >
                Check inventory ↗
              </Link>
            </div>
            <div className="design-related">
              {relatedDesigns.map((design) => (
                <Link href={`/designs/${design.slug}/`} key={design.slug}>
                  <img
                    src={design.image}
                    alt={`${design.code} ${design.name} granite monument design`}
                    loading="lazy"
                  />
                  <span>{design.code}</span>
                  <strong>{design.name}</strong>
                </Link>
              ))}
            </div>
          </div>
        </section>
      ) : null}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
      />
      <SeoBreadcrumbs
        items={[
          { name: "Home", path: "/" },
          { name: "Granite Colors", path: "/granite-colors/" },
          { name: color.name, path: `/colors/${color.slug}/` }
        ]}
      />
    </>
  );
}
