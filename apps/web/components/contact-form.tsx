"use client";

import Script from "next/script";
import { FormEvent, useState } from "react";

declare global {
  interface Window {
    turnstile?: { reset: () => void };
  }
}

export function ContactForm() {
  const [status, setStatus] = useState<{
    kind: "idle" | "sending" | "success" | "error";
    message: string;
  }>({ kind: "idle", message: "" });

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    if (!formData.get("cf-turnstile-response")) {
      setStatus({
        kind: "error",
        message: "Please complete the security check before submitting."
      });
      return;
    }

    setStatus({ kind: "sending", message: "Sending your request…" });
    try {
      const response = await fetch("/contact-submit.php", {
        method: "POST",
        body: formData
      });
      const result = (await response.json()) as {
        success?: boolean;
        message?: string;
      };
      if (!response.ok || !result.success) {
        throw new Error(result.message || "We could not send your request.");
      }
      setStatus({
        kind: "success",
        message: result.message || "Thank you. Our team will be in touch."
      });
      form.reset();
      window.turnstile?.reset();
    } catch (error) {
      setStatus({
        kind: "error",
        message:
          error instanceof Error
            ? error.message
            : "We could not send your request. Please call us directly."
      });
      window.turnstile?.reset();
    }
  }

  return (
    <>
      <Script src="https://challenges.cloudflare.com/turnstile/v0/api.js" strategy="afterInteractive" />
      <form className="contact-form" action="/contact-submit.php" method="post" onSubmit={submit}>
        <input type="hidden" name="mauticform[formId]" value="1" />
        <input type="hidden" name="mauticform[return]" value="" />
        <input type="hidden" name="mauticform[formName]" value="contact_us" />
        <div className="honeypot" aria-hidden="true">
          <label>
            Leave blank
            <input type="text" name="mauticform[email2]" tabIndex={-1} autoComplete="off" />
          </label>
        </div>
        <div className="field-row">
          <label>
            Name
            <input name="mauticform[f_name]" type="text" autoComplete="name" required />
          </label>
          <label>
            Business email
            <input name="mauticform[email]" type="email" autoComplete="email" required />
          </label>
        </div>
        <label>
          Phone
          <input
            name="mauticform[phone]"
            type="tel"
            autoComplete="tel"
            inputMode="tel"
            pattern="^\+?[0-9\s\-()]{10,}$"
            required
          />
        </label>
        <label>
          What do you need?
          <textarea
            name="mauticform[f_message]"
            rows={6}
            minLength={10}
            placeholder="Include the design number, granite color, size, quantity and delivery location when available."
            required
          />
        </label>
        <div
          className="cf-turnstile"
          data-sitekey="0x4AAAAAACt2E8Px0M9iEK9G"
        />
        <button className="button button--gold" type="submit" disabled={status.kind === "sending"}>
          {status.kind === "sending" ? "Sending…" : "Send request"}
        </button>
        {status.kind !== "idle" ? (
          <p className={`form-status form-status--${status.kind}`} role="status">
            {status.message}
          </p>
        ) : null}
      </form>
    </>
  );
}
