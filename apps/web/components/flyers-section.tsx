import Link from "next/link";
import { flyers } from "@/data/flyers";
import { SectionHeading } from "./section-heading";

export function FlyersSection() {
  return (
    <section className="section flyer-section" aria-labelledby="flyers-heading">
      <div className="shell">
        <div className="section-heading-row">
          <div id="flyers-heading">
            <SectionHeading
              eyebrow="Current dealer materials"
              title="Special flyers"
              description="Open or download current Angel Granites color, convention and in-stock design flyers."
            />
          </div>
          <Link className="text-link" href="/flyers/">
            View all flyers ↗
          </Link>
        </div>
        <div className="flyer-grid">
          {flyers.map((flyer) => (
            <a
              className="flyer-card"
              href={flyer.pdfPath}
              target="_blank"
              rel="noopener noreferrer"
              key={flyer.id}
            >
              <span className="flyer-image">
                <img
                  src={flyer.imagePath}
                  alt={`${flyer.name} cover`}
                  loading="lazy"
                />
              </span>
              <span className="flyer-copy">
                <small>{flyer.label}</small>
                <strong>{flyer.name}</strong>
                <span>Open PDF ↗</span>
              </span>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}
