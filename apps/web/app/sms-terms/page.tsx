import type { Metadata } from "next";
import Link from "next/link";
import { LegalLayout } from "@/components/legal-layout";
import { pageOpenGraph } from "@/data/seo";

const description =
  "Angel Stones SMS communication policies, message frequency, opt-out procedures and data-rate information.";
export const metadata: Metadata = {
  title: "SMS Terms and Conditions",
  description,
  alternates: { canonical: "/sms-terms/" },
  openGraph: pageOpenGraph(
    "SMS Terms and Conditions | Angel Granites",
    description,
    "/sms-terms/"
  )
};

export default function SmsTermsPage() {
  return (
    <LegalLayout
      title="SMS Terms and Conditions"
      description="Information about consent, message frequency, opt-out procedures, carrier charges and SMS data handling."
      effectiveDate="July 12, 2025"
      sections={[
        {
          id: "consent",
          title: "Consent to receive messages",
          content: (
            <p>
              By providing your mobile number, you consent to receive SMS
              communications from Angel Stones regarding quotes, order updates,
              delivery notifications and promotional offers.
            </p>
          )
        },
        {
          id: "message-frequency",
          title: "Message frequency",
          content: (
            <p>
              Message frequency may vary. Typical messages include order status
              updates, reminders and occasional promotional messages.
            </p>
          )
        },
        {
          id: "opt-out",
          title: "Opt-out and help",
          content: (
            <p>
              You can opt out at any time by replying &quot;STOP&quot; to any
              message. For assistance, reply &quot;HELP&quot; or contact us at{" "}
              <a href="mailto:info@theangelstones.com">
                info@theangelstones.com
              </a>
              .
            </p>
          )
        },
        {
          id: "charges",
          title: "Charges",
          content: (
            <p>
              Message and data rates may apply depending on your mobile plan.
              Check with your carrier for details.
            </p>
          )
        },
        {
          id: "data-collection",
          title: "Data collection",
          content: (
            <p>
              We may collect and store your phone number, message content and
              related metadata. For more details, review our{" "}
              <Link href="/privacy-policy/">Privacy Policy</Link>.
            </p>
          )
        }
      ]}
    />
  );
}
