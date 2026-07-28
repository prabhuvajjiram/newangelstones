import Link from "next/link";

export default function NotFound() {
  return (
    <section className="page-hero">
      <div className="shell">
        <span className="eyebrow eyebrow--light">404 · Page not found</span>
        <h1>That stone may have moved.</h1>
        <p>
          Return to the inventory to continue browsing granite monument designs
          and colors.
        </p>
        <div className="hero-actions">
          <Link className="button button--gold" href="/inventory/">
            Browse inventory
          </Link>
          <Link className="button button--outline" href="/">
            Return home
          </Link>
        </div>
      </div>
    </section>
  );
}
