import Script from "next/script";

const fallbackMeasurementId = "G-Y5TBVH7CY7";

export function GoogleAnalytics() {
  const configuredId =
    process.env.NEXT_PUBLIC_GA_MEASUREMENT_ID?.trim() || fallbackMeasurementId;
  const measurementId = /^G-[A-Z0-9]+$/i.test(configuredId)
    ? configuredId
    : fallbackMeasurementId;

  return (
    <>
      <Script
        id="google-analytics-library"
        src={`https://www.googletagmanager.com/gtag/js?id=${measurementId}`}
        strategy="lazyOnload"
      />
      <Script id="google-analytics-config" strategy="lazyOnload">
        {`
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', '${measurementId}', { anonymize_ip: true });
        `}
      </Script>
    </>
  );
}
