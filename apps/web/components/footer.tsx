import Link from "next/link";
import { navigation, site } from "@/data/site";

export function Footer() {
  return (
    <footer className="site-footer">
      <div className="shell footer-grid">
        <div>
          <img
            className="footer-logo"
            src="/images/ag_logo.svg"
            alt="Angel Granites"
            width="82"
            height="82"
          />
          <p>
            Granite monuments, memorials and wholesale stone supply from the
            granite capital of the world.
          </p>
          <p className="footer-relationship">{site.relationship}</p>
          <nav className="footer-social" aria-label="Angel Granites social media">
            <a href={site.facebookUrl} target="_blank" rel="noopener noreferrer">
              Facebook
            </a>
            <a href={site.instagramUrl} target="_blank" rel="noopener noreferrer">
              Instagram
            </a>
          </nav>
        </div>
        <div>
          <h2>Explore</h2>
          <ul>
            {navigation.map((item) => (
              <li key={item.href}>
                <Link href={item.href}>{item.label}</Link>
              </li>
            ))}
            <li><Link href="/flyers/">Special Flyers</Link></li>
            <li><Link href="/locations/">Locations</Link></li>
          </ul>
        </div>
        <div>
          <h2>Contact</h2>
          <address>
            {site.primaryAddress}
            <br />
            {site.secondaryElbertonAddress}
            <br />
            {site.barreAddress}
            <br />
            <a className="footer-contact-link" href={site.phoneHref}>
              {site.phone}
            </a>
            <br />
            <a
              className="footer-contact-link"
              href={site.alternateOfficePhoneHref}
            >
              Alternative office: {site.alternateOfficePhone}
            </a>
            <br />
            <a
              className="footer-contact-link"
              href={`mailto:${site.email}`}
            >
              {site.email}
            </a>
            <br />
            <a
              className="footer-payment-link"
              href={site.paymentUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              Pay invoice securely with Clover
            </a>
          </address>
        </div>
      </div>
      <div className="shell footer-bottom">
        <span>© {new Date().getFullYear()} Angel Stones. All rights reserved.</span>
        <div>
          <Link href="/privacy-policy/">Privacy</Link>
          <Link href="/terms-of-service/">Terms</Link>
          <Link href="/sms-terms/">SMS terms</Link>
        </div>
      </div>
    </footer>
  );
}
