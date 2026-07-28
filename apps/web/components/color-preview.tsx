import Link from "next/link";
import { graniteColors } from "@/data/catalog";
import { SectionHeading } from "./section-heading";

export function ColorPreview() {
  return (
    <section className="section section--ink" aria-labelledby="colors-heading">
      <div className="shell">
        <div className="section-heading-row section-heading-row--light">
          <SectionHeading
            eyebrow="Granite palette"
            title="Color defines the memorial"
            description="Compare natural granite colors selected for monument work, engraving contrast and lasting outdoor performance."
          />
          <Link className="text-link text-link--light" href="/granite-colors/">
            Browse all colors <span aria-hidden="true">↗</span>
          </Link>
        </div>
        <div className="color-strip">
          {graniteColors.slice(0, 8).map((color) => (
            <Link href={`/colors/${color.slug}/`} key={color.slug}>
              <span className="color-swatch">
                <img
                  src={color.image}
                  alt={`${color.name} polished stone color sample`}
                  loading="lazy"
                />
              </span>
              <strong>
                {color.name.replace(
                  / (?:Granite|Quartzite|Marble|Sandstone)$/i,
                  ""
                )}
              </strong>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
