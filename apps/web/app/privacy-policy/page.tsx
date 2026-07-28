import type { Metadata } from "next";
import Link from "next/link";
import { LegalLayout } from "@/components/legal-layout";
import { pageOpenGraph } from "@/data/seo";

const description =
  "Learn how Angel Stones collects, uses and protects information provided through our website, services and communications.";
export const metadata: Metadata = {
  title: "Privacy Policy",
  description,
  alternates: { canonical: "/privacy-policy/" },
  openGraph: pageOpenGraph(
    "Privacy Policy | Angel Granites",
    description,
    "/privacy-policy/"
  )
};

export default function PrivacyPolicyPage() {
  return (
    <LegalLayout
      title="Privacy Policy"
      description="How Angel Stones collects, uses, protects and responds to requests concerning personal information."
      effectiveDate="July 12, 2025"
      sections={[
        {
          id: "overview",
          title: "Overview",
          content: (
            <p>
              Angel Stones (&quot;we,&quot; &quot;our,&quot; or &quot;us&quot;)
              values your privacy. This Privacy Policy outlines how we collect,
              use and protect your information when you visit our website or
              interact with us.
            </p>
          )
        },
        {
          id: "information-we-collect",
          title: "Information we collect",
          content: (
            <ul>
              <li>Personal information such as name, email and phone number</li>
              <li>Order and transaction details</li>
              <li>Communication history, including emails and SMS</li>
              <li>Website usage data, including cookies and analytics</li>
            </ul>
          )
        },
        {
          id: "how-we-use-information",
          title: "How we use your information",
          content: (
            <ul>
              <li>To process orders and fulfill services</li>
              <li>To provide customer support</li>
              <li>To send order updates and promotional communications</li>
              <li>To improve our website and services</li>
            </ul>
          )
        },
        {
          id: "sms-communication",
          title: "SMS communication",
          content: (
            <p>
              By providing your phone number, you consent to receive text
              messages from Angel Stones. Message and data rates may apply. You
              can opt out at any time by replying STOP. Review our{" "}
              <Link href="/sms-terms/">SMS Terms and Conditions</Link> for
              additional information.
            </p>
          )
        },
        {
          id: "data-protection",
          title: "Data protection",
          content: (
            <p>
              We take reasonable measures to protect your information against
              unauthorized access, disclosure or loss.
            </p>
          )
        },
        {
          id: "your-rights",
          title: "Your rights",
          content: (
            <p>
              You can request access to or deletion of your personal data by
              contacting us at{" "}
              <a href="mailto:info@theangelstones.com">
                info@theangelstones.com
              </a>
              . Your use of our services is also governed by our{" "}
              <Link href="/terms-of-service/">Terms of Service</Link>.
            </p>
          )
        }
      ]}
    />
  );
}
