import type { Metadata } from "next";
import Link from "next/link";
import { preload } from "react-dom";
import { InventoryBrowser } from "@/components/inventory-browser";
import { SectionHeading } from "@/components/section-heading";
import { inventoryImageIndex } from "@/data/inventory-images";
import { pageOpenGraph } from "@/data/seo";

const description =
  "Search current Angel Granites monument inventory by type, color, design, finish, size and location in Elberton and Barre.";
export const metadata: Metadata = {
  title: "Current Granite Monument Inventory",
  description,
  alternates: { canonical: "/inventory/" },
  openGraph: pageOpenGraph(
    "Current Granite Monument Inventory | Angel Granites",
    description,
    "/inventory/"
  )
};

export default function InventoryPage() {
  preload("/images/Factory-1920.webp", {
    as: "image",
    fetchPriority: "high"
  });
  preload(
    "/inventory-proxy.php?page=1&pageSize=1000&format=compact",
    {
      as: "fetch",
      crossOrigin: "anonymous",
      fetchPriority: "high"
    }
  );

  return (
    <>
      <section className="page-hero page-hero--inventory">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/" prefetch={false}>Home</Link><span>/</span><span>Inventory</span>
          </nav>
          <span className="eyebrow eyebrow--light">Live inventory</span>
          <h1>Current granite inventory</h1>
          <p>
            Search available stock by design, granite color, size, finish and
            location. Inventory is supplied directly by our inventory service.
          </p>
        </div>
      </section>
      <section className="section section--paper inventory-section">
        <div className="inventory-shell">
          <InventoryBrowser imageIndex={inventoryImageIndex} />
        </div>
      </section>
      <section className="section inventory-seo-guide">
        <div className="shell">
          <SectionHeading
            eyebrow="Using live stock"
            title="Move from an inventory match to a confirmed monument order."
            description="The inventory search helps dealers locate candidate stones quickly. Availability, component details and production requirements should still be confirmed before quoting a final delivery date."
          />
          <div className="advantage-grid">
            <article>
              <h3>Search the way specifications are written</h3>
              <p>
                Enter a design number, monument type, granite color, finish or
                dimension. The search also recognizes common feet-and-inches
                size notation and alternate dimension order.
              </p>
            </article>
            <article>
              <h3>Open the stock details</h3>
              <p>
                Review the available quantity, warehouse location and linked
                crate or container information. Product photography is shown
                first when a verified design image is available.
              </p>
            </article>
            <article>
              <h3>Confirm before committing</h3>
              <p>
                Send the design or product code, required quantity, delivery
                destination and any finish or artwork changes so our team can
                verify the complete requirement.
              </p>
            </article>
          </div>
          <nav className="collection-guide-links" aria-label="Inventory resources">
            <Link href="/resources/how-to-read-monument-dimensions/">
              How to read monument dimensions
            </Link>
            <Link href="/resources/granite-monument-ordering-checklist/">
              Monument ordering checklist
            </Link>
            <Link href="/granite-colors/">Compare granite colors</Link>
            <Link href="/contact/">Request confirmed pricing</Link>
          </nav>
        </div>
      </section>
    </>
  );
}
