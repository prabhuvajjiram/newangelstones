"use client";

import Link from "next/link";
import { useState } from "react";
import { ThemeSelector } from "@/components/theme-selector";
import { navigation, site } from "@/data/site";

export function Header() {
  const [open, setOpen] = useState(false);

  return (
    <header className="site-header">
      <div className="utility-bar">
        <div className="shell utility-inner">
          <span>Wholesale monument supply · Elberton, Georgia + Barre, Vermont</span>
          <div>
            <a href={site.phoneHref}>{site.phone}</a>
            <a className="utility-email" href={`mailto:${site.email}`}>
              {site.email}
            </a>
            <a
              className="utility-payment"
              href={site.paymentUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              Pay invoice
            </a>
            <ThemeSelector />
          </div>
        </div>
      </div>
      <div className="shell nav-shell">
        <Link
          className="brand"
          href="/"
          prefetch={false}
          aria-label="Angel Granites home"
        >
          <img
            src="/images/ag_logo.svg"
            alt="Angel Granites logo"
            width="64"
            height="64"
          />
          <span aria-hidden="true">
            <strong>Angel</strong>
            <small>Granites</small>
            <em>{site.relationship}</em>
          </span>
        </Link>
        <button
          className="menu-toggle"
          type="button"
          aria-expanded={open}
          aria-controls="primary-navigation"
          onClick={() => setOpen((current) => !current)}
        >
          <span />
          <span />
          <span />
          <span className="sr-only">Toggle navigation</span>
        </button>
        <nav
          id="primary-navigation"
          className={open ? "primary-nav is-open" : "primary-nav"}
          aria-label="Primary navigation"
        >
          {navigation.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              prefetch={false}
              onClick={() => setOpen(false)}
            >
              {item.label}
            </Link>
          ))}
          <Link
            className="nav-cta"
            href="/contact/"
            prefetch={false}
            onClick={() => setOpen(false)}
          >
            Request pricing
          </Link>
        </nav>
      </div>
    </header>
  );
}
