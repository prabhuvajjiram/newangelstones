import Link from "next/link";
import { featuredCollections } from "@/data/catalog";
import { SectionHeading } from "./section-heading";

export function FeaturedCollections() {
  return (
    <section className="section section--paper" aria-labelledby="featured-heading">
      <div className="shell">
        <div className="section-heading-row">
          <SectionHeading
            eyebrow="Featured products"
            title="Start with a proven design"
            description="Browse the collections our monument dealers request most often. Contact us to confirm current stock, color and dimensions."
          />
          <Link className="text-link" href="/inventory/">
            View all inventory <span aria-hidden="true">↗</span>
          </Link>
        </div>
        <div className="featured-grid">
          {featuredCollections.map((collection, index) => (
            <Link
              className={`featured-card featured-card--${index + 1}`}
              href={collection.href}
              key={collection.id}
            >
              <img
                src={collection.imagePath}
                alt={`${collection.name} granite product collection`}
                loading={index < 2 ? "eager" : "lazy"}
              />
              <span className="featured-overlay" />
              <span className="featured-copy">
                <strong>{collection.name}</strong>
                <span>Explore collection</span>
              </span>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
