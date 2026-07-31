import type { Metadata } from "next";
import { Footer } from "@/components/footer";
import { GoogleAnalytics } from "@/components/google-analytics";
import { Header } from "@/components/header";
import { SupportTools } from "@/components/support-tools";
import { site } from "@/data/site";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL(site.url),
  title: {
    default: "Granite Monuments & Memorial Supply | Angel Granites",
    template: "%s | Angel Granites"
  },
  description:
    "Wholesale granite monuments, memorials, benches and custom stone designs from Elberton, Georgia. Browse in-stock designs and granite colors.",
  applicationName: site.name,
  alternates: { canonical: "/" },
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-image-preview": "large",
      "max-snippet": -1,
      "max-video-preview": -1
    }
  },
  openGraph: {
    type: "website",
    siteName: site.name,
    url: site.url,
    locale: "en_US",
    images: [
      {
        url: site.socialImage,
        width: 1200,
        height: 675,
        alt: site.socialImageAlt
      }
    ]
  },
  twitter: {
    card: "summary_large_image",
    images: [site.socialImage]
  },
  icons: {
    icon: "/favicon.ico",
    apple: "/apple-icon-180x180.png"
  }
};

export default function RootLayout({
  children
}: Readonly<{ children: React.ReactNode }>) {
  const organizationSchema = {
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": `${site.url}/#organization`,
    name: site.name,
    legalName: site.legalName,
    url: site.url,
    logo: `${site.url}/images/ag_logo.svg`,
    telephone: site.phone,
    email: site.email,
    areaServed: "United States",
    contactPoint: [
      {
        "@type": "ContactPoint",
        telephone: site.phone,
        email: site.email,
        contactType: "sales and customer service",
        areaServed: "US"
      },
      {
        "@type": "ContactPoint",
        telephone: site.alternateOfficePhone,
        email: site.email,
        contactType: "alternate office",
        areaServed: "US"
      }
    ],
    location: [
      {
        "@type": "Place",
        name: "Angel Granites Elberton",
        address: {
          "@type": "PostalAddress",
          streetAddress: "1187 Old Middleton Rd",
          addressLocality: "Elberton",
          addressRegion: "GA",
          postalCode: "30635",
          addressCountry: "US"
        }
      },
      {
        "@type": "Place",
        name: "Angel Granites Barre warehouse",
        address: {
          "@type": "PostalAddress",
          streetAddress: "15 Blackwell St",
          addressLocality: "Barre",
          addressRegion: "VT",
          postalCode: "05641",
          addressCountry: "US"
        }
      }
    ]
  };

  return (
    <html lang="en" suppressHydrationWarning>
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=Playfair+Display:wght@400;500;600&display=swap"
        />
        <script
          dangerouslySetInnerHTML={{
            __html:
              '(function(){try{var p=localStorage.getItem("angel-theme")||"dark";var t=p==="system"?(matchMedia("(prefers-color-scheme: light)").matches?"light":"dark"):p;document.documentElement.dataset.theme=t;document.documentElement.dataset.themePreference=p;document.documentElement.style.colorScheme=t}catch(e){document.documentElement.dataset.theme="dark"}})();'
          }}
        />
      </head>
      <body>
        <a className="skip-link" href="#main-content">
          Skip to content
        </a>
        <Header />
        <main id="main-content">{children}</main>
        <Footer />
        <SupportTools />
        <GoogleAnalytics />
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(organizationSchema) }}
        />
      </body>
    </html>
  );
}
