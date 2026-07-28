import type { Metadata } from "next";
import { LegalLayout } from "@/components/legal-layout";
import { pageOpenGraph } from "@/data/seo";

const description =
  "Terms governing access to and use of the Angel Stones website, products and services.";
export const metadata: Metadata = {
  title: "Terms of Service",
  description,
  alternates: { canonical: "/terms-of-service/" },
  openGraph: pageOpenGraph(
    "Terms of Service | Angel Granites",
    description,
    "/terms-of-service/"
  )
};

export default function TermsOfServicePage() {
  return (
    <LegalLayout
      title="Terms of Service"
      description="The terms governing access to and use of our website, products and services."
      effectiveDate="July 12, 2025"
      sections={[
        {
          id: "introduction",
          title: "1. Introduction",
          content: (
            <p>
              Welcome to Angel Stones. These Terms of Service
              (&quot;Terms&quot;) govern your access to and use of our website,
              products and services. By using our services, you agree to be
              bound by these Terms.
            </p>
          )
        },
        {
          id: "orders-payments",
          title: "2. Orders and payments",
          content: (
            <p>
              All orders are subject to acceptance and availability. Prices
              are subject to change without notice. Payment terms will be
              agreed upon at the time of order placement.
            </p>
          )
        },
        {
          id: "shipping-delivery",
          title: "3. Shipping and delivery",
          content: (
            <p>
              We make every effort to ensure timely delivery. However, delivery
              dates are estimates and not guaranteed.
            </p>
          )
        },
        {
          id: "returns-refunds",
          title: "4. Returns and refunds",
          content: (
            <p>
              Custom products may not be eligible for return. Please contact us
              immediately if there is an issue with your order.
            </p>
          )
        },
        {
          id: "limitation-liability",
          title: "5. Limitation of liability",
          content: (
            <p>
              Angel Stones is not liable for indirect, incidental or
              consequential damages. Our total liability shall not exceed the
              amount paid for the specific product or service.
            </p>
          )
        },
        {
          id: "intellectual-property",
          title: "6. Intellectual property",
          content: (
            <p>
              All content on our website, including images and text, is the
              property of Angel Stones and may not be used without permission.
            </p>
          )
        },
        {
          id: "governing-law",
          title: "7. Governing law",
          content: (
            <p>
              These Terms are governed by the laws of the State of Georgia,
              USA.
            </p>
          )
        },
        {
          id: "changes",
          title: "8. Changes",
          content: (
            <p>
              We reserve the right to modify these Terms at any time. Continued
              use of our services indicates acceptance of the modified Terms.
            </p>
          )
        }
      ]}
    />
  );
}
