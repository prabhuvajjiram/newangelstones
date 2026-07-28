import type { Metadata } from "next";
import Link from "next/link";
import { ContactForm } from "@/components/contact-form";
import { pageOpenGraph } from "@/data/seo";
import { site } from "@/data/site";

const description =
  "Contact Angel Granites for monument availability, wholesale pricing, custom granite production and nationwide shipping support.";
export const metadata: Metadata = {
  title: "Contact & Request Granite Pricing",
  description,
  alternates: { canonical: "/contact/" },
  openGraph: pageOpenGraph(
    "Contact & Request Granite Pricing | Angel Granites",
    description,
    "/contact/"
  )
};

export default function ContactPage() {
  return (
    <>
      <section className="page-hero">
        <div className="shell">
          <nav className="breadcrumbs" aria-label="Breadcrumb">
            <Link href="/">Home</Link><span>/</span><span>Contact</span>
          </nav>
          <span className="eyebrow eyebrow--light">Dealer and wholesale support</span>
          <h1>Tell us what the job requires.</h1>
          <p>
            Share the design, color, dimensions, quantity and delivery location.
            We will help confirm the next available path.
          </p>
        </div>
      </section>
      <section className="section contact-section">
        <div className="shell contact-grid">
          <div className="contact-details">
            <span className="eyebrow">Direct contact</span>
            <h2>Work with a team that understands monument specifications.</h2>
            <p>
              For faster answers, include the design number and granite color you
              are considering.
            </p>
            <div className="contact-list">
              <div><small>Primary phone</small><a href={site.phoneHref}>{site.phone}</a></div>
              <div>
                <small>Alternative office phone</small>
                <a href={site.alternateOfficePhoneHref}>
                  {site.alternateOfficePhone}
                </a>
              </div>
              <div><small>Email</small><a href={`mailto:${site.email}`}>{site.email}</a></div>
              <div>
                <small>Elberton production & inventory</small>
                <address>
                  {site.primaryAddress}<br />{site.secondaryElbertonAddress}
                </address>
              </div>
              <div>
                <small>Barre warehouse</small>
                <address>{site.barreAddress}</address>
              </div>
              <div><small>Mailing address</small><address>{site.mailingAddress}</address></div>
              <div><small>Corporate address</small><address>{site.corporateAddress}</address></div>
            </div>
          </div>
          <ContactForm />
        </div>
      </section>
    </>
  );
}
