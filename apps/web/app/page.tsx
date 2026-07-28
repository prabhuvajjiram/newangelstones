import Link from "next/link";
import { preload } from "react-dom";
import { ColorPreview } from "@/components/color-preview";
import { FeaturedCollections } from "@/components/featured-collections";
import { FlyersSection } from "@/components/flyers-section";
import { SectionHeading } from "@/components/section-heading";
import { locations, services } from "@/data/business";
import { resourceArticles } from "@/data/resources";
import { site } from "@/data/site";

export default function HomePage() {
  preload("/images/video-poster-mobile.webp", {
    as: "image",
    media: "(max-width: 700px)",
    fetchPriority: "high"
  });
  preload("/images/as-mobile.webm", {
    as: "video",
    type: "video/webm",
    media: "(max-width: 700px)",
    fetchPriority: "high"
  });

  const websiteSchema = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: site.name,
    alternateName: site.legalName,
    url: site.url,
    publisher: {
      "@type": "Organization",
      name: site.name,
      url: site.url
    }
  };

  return (
    <>
      <section className="hero">
        <video
          className="hero-image hero-video"
          autoPlay
          muted
          loop
          playsInline
          poster="/images/video-poster-mobile.webp"
          preload="auto"
          aria-hidden="true"
        >
          <source
            src="/images/as-mobile.webm"
            type="video/webm"
            media="(max-width: 700px)"
          />
          <source
            src="/images/as-mobile.mp4"
            type="video/mp4"
            media="(max-width: 700px)"
          />
          <source src="/images/as.webm" type="video/webm" />
          <source src="/images/as.mp4" type="video/mp4" />
        </video>
        <span className="hero-shade" />
        <div className="shell hero-inner">
          <div className="hero-copy">
            <span className="eyebrow eyebrow--light">From quarry to memorial</span>
            <h1>Granite monuments, built for the people who stand behind them.</h1>
            <p>
              Direct manufacturing, dependable U.S. inventory and responsive
              wholesale support for monument dealers across the country.
            </p>
            <div className="hero-actions">
              <Link
                className="button button--gold"
                href="/inventory/"
                prefetch={false}
              >
                Browse inventory
              </Link>
              <Link
                className="button button--outline"
                href="/contact/"
                prefetch={false}
              >
                Request a quote
              </Link>
            </div>
          </div>
          <div className="hero-note">
            <strong>Elberton, GA + Barre, VT</strong>
            <span>In-stock designs ready to ship</span>
            <span>Custom production to specification</span>
            <span>Nationwide dealer support</span>
          </div>
        </div>
      </section>

      <section className="trust-bar" aria-label="Business capabilities">
        <div className="shell trust-grid">
          <div>
            <strong>100+</strong>
            <span>Granite color options</span>
          </div>
          <div>
            <strong>U.S.</strong>
            <span>Inventory support in Elberton and Barre</span>
          </div>
          <div>
            <strong>Custom</strong>
            <span>Sizes, finishes and artwork</span>
          </div>
          <div>
            <strong>Nationwide</strong>
            <span>Freight coordination</span>
          </div>
        </div>
      </section>

      <section className="section intro-section">
        <div className="shell intro-grid">
          <div className="intro-image-stack">
            <img
              className="intro-image-main"
              src="/images/as-welcome.webp"
              alt="Finished granite memorial produced by Angel Granites"
            />
            <img
              className="intro-image-detail"
              src="/images/quarry-cropped.webp"
              alt="Granite quarry stone"
            />
          </div>
          <div className="intro-copy">
            <SectionHeading
              eyebrow="A better supply relationship"
              title="Stone knowledge. Production control. Straight answers."
              description="Angel Granites supports monument dealers from first design review through manufacturing and delivery. Our team helps you confirm the details that matter before the stone moves."
            />
            <div className="principles">
              <div>
                <span>01</span>
                <div>
                  <h3>Inventory you can see</h3>
                  <p>Browse real product photography and ask our team to verify current availability.</p>
                </div>
              </div>
              <div>
                <span>02</span>
                <div>
                  <h3>Specifications understood</h3>
                  <p>Color, finish, dimensions, artwork and packing requirements stay connected.</p>
                </div>
              </div>
              <div>
                <span>03</span>
                <div>
                  <h3>Built for your business</h3>
                  <p>Wholesale service designed around monument dealers and memorial professionals.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <FlyersSection />
      <FeaturedCollections />
      <ColorPreview />

      <section className="section section--paper">
        <div className="shell">
          <div className="section-heading-row">
            <SectionHeading
              eyebrow="Products and services"
              title="Monument supply through finished stone."
              description="Wholesale granite, custom fabrication, sandblasting and etching coordinated around confirmed dealer specifications."
            />
            <Link className="text-link" href="/products-services/">
              Explore all services ↗
            </Link>
          </div>
          <div className="service-grid service-grid--home">
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

      <section className="section process-section">
        <div className="shell">
          <SectionHeading
            eyebrow="How we work"
            title="A clear path from design to delivery"
            description="Send the details you have. We will help close the gaps, confirm availability and prepare the next step."
            align="center"
          />
          <div className="process-grid">
            {[
              ["01", "Select", "Choose an in-stock design or share a custom reference."],
              ["02", "Specify", "Confirm granite color, dimensions, finish and artwork."],
              ["03", "Approve", "Review availability, pricing and production requirements."],
              ["04", "Deliver", "Coordinate packing, freight and delivery to your location."]
            ].map(([number, title, description]) => (
              <div className="process-card" key={number}>
                <span>{number}</span>
                <h3>{title}</h3>
                <p>{description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="shell">
          <div className="section-heading-row">
            <SectionHeading
              eyebrow="Elberton and Barre"
              title="Two granite-industry locations. One connected team."
              description="Angel Granites supports monument production, inventory and dealer orders through Elberton, Georgia and Barre, Vermont."
            />
            <Link className="text-link" href="/locations/">
              View locations ↗
            </Link>
          </div>
          <div className="location-grid">
            {locations.map((location) => (
              <article className="location-card" key={location.slug}>
                <span className="eyebrow">{location.eyebrow}</span>
                <h2>{location.name}</h2>
                <p>{location.description}</p>
                <address>
                  {location.addressLines.map((address) => (
                    <span key={address}>{address}</span>
                  ))}
                </address>
                <Link className="text-link" href={`/locations/${location.slug}/`}>
                  Explore {location.shortName} ↗
                </Link>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section resource-preview-section">
        <div className="shell">
          <div className="section-heading-row">
            <SectionHeading
              eyebrow="Dealer learning center"
              title="Specify monument work with more confidence."
              description="Original reference guides for monument terminology, common shapes, granite finishes, dimensions and order preparation."
            />
            <Link className="text-link" href="/resources/">
              Visit the learning center ↗
            </Link>
          </div>
          <div className="resource-grid">
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
        </div>
      </section>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(websiteSchema) }}
      />

      <section className="cta-section">
        <div className="shell cta-inner">
          <div>
            <span className="eyebrow eyebrow--light">Have a design in mind?</span>
            <h2>Let’s confirm the stone, size and next available path.</h2>
          </div>
          <Link className="button button--cream" href="/contact/">
            Talk with our team
          </Link>
        </div>
      </section>
    </>
  );
}
