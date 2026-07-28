"use client";

import { useEffect, useRef, useState } from "react";
import { site } from "@/data/site";

type DeviceFamily = "ios" | "android" | "windows" | "desktop";

function AppleIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M16.8 12.7c0-2.3 1.9-3.4 2-3.5a4.3 4.3 0 0 0-3.4-1.8c-1.4-.1-2.8.9-3.5.9-.8 0-1.9-.9-3.1-.9a4.6 4.6 0 0 0-3.9 2.4c-1.7 2.9-.4 7.2 1.2 9.5.8 1.1 1.7 2.4 3 2.3 1.2 0 1.7-.7 3.2-.7s1.9.7 3.2.7 2.1-1.1 2.9-2.2a10 10 0 0 0 1.3-2.7 4.1 4.1 0 0 1-2.9-4Zm-2.3-6.8a4.1 4.1 0 0 0 1-3 4.3 4.3 0 0 0-2.8 1.4 3.9 3.9 0 0 0-1 2.9 3.6 3.6 0 0 0 2.8-1.3Z" />
    </svg>
  );
}

function AndroidIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="m7.2 5.2-1.4-2a.5.5 0 0 1 .1-.7.5.5 0 0 1 .7.1L8 4.7a9.1 9.1 0 0 1 8 0l1.4-2.1a.5.5 0 0 1 .7-.1.5.5 0 0 1 .1.7l-1.4 2A7.3 7.3 0 0 1 20 11H4a7.3 7.3 0 0 1 3.2-5.8ZM8 8.5a.8.8 0 1 0 0-1.6.8.8 0 0 0 0 1.6Zm8 0a.8.8 0 1 0 0-1.6.8.8 0 0 0 0 1.6ZM4 12h16v7a2 2 0 0 1-2 2h-1v1.5a1 1 0 0 1-2 0V21H9v1.5a1 1 0 0 1-2 0V21H6a2 2 0 0 1-2-2v-7Z" />
    </svg>
  );
}

function WindowsIcon() {
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
      <path d="M3 4.8 10.5 3.7v7.6H3V4.8Zm8.6-1.3L21 2v9.3h-9.4V3.5ZM3 12.5h7.5v7.7L3 19.1v-6.6Zm8.6 0H21V22l-9.4-1.6v-7.9Z" />
    </svg>
  );
}

function PhoneIcon() {
  return (
    <svg
      className="app-launcher__outline-icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
      focusable="false"
    >
      <rect x="7" y="2.5" width="10" height="19" rx="2.2" />
      <path d="M10 5h4M11 18.5h2" />
    </svg>
  );
}

export function SupportTools() {
  const [device, setDevice] = useState<DeviceFamily>("desktop");
  const [appMenuOpen, setAppMenuOpen] = useState(false);
  const appLauncherRef = useRef<HTMLElement>(null);

  useEffect(() => {
    const userAgent = navigator.userAgent;
    if (/iPhone|iPad|iPod/i.test(userAgent)) {
      setDevice("ios");
    } else if (/Android/i.test(userAgent)) {
      setDevice("android");
    } else if (/Windows NT/i.test(userAgent)) {
      setDevice("windows");
    }

    let loaded = false;
    const events = ["pointerdown", "scroll", "keydown", "touchstart"] as const;
    const loadChat = () => {
      if (loaded || document.getElementById("angel-tawk-chat")) return;
      loaded = true;
      const chatWindow = window as Window & {
        Tawk_API?: Record<string, unknown>;
        Tawk_LoadStart?: Date;
      };
      chatWindow.Tawk_API ??= {};
      chatWindow.Tawk_LoadStart = new Date();
      const script = document.createElement("script");
      script.id = "angel-tawk-chat";
      script.async = true;
      script.src =
        "https://embed.tawk.to/665adcb29a809f19fb37a0d0/1hv9es6en";
      script.charset = "UTF-8";
      script.crossOrigin = "anonymous";
      document.head.appendChild(script);
      events.forEach((event) => window.removeEventListener(event, loadChat));
    };

    const timer = window.setTimeout(loadChat, 5000);
    events.forEach((event) =>
      window.addEventListener(event, loadChat, { passive: true, once: true })
    );

    return () => {
      window.clearTimeout(timer);
      events.forEach((event) => window.removeEventListener(event, loadChat));
    };
  }, []);

  useEffect(() => {
    if (!appMenuOpen) return;

    const closeOnOutsideClick = (event: PointerEvent) => {
      if (
        appLauncherRef.current &&
        !appLauncherRef.current.contains(event.target as Node)
      ) {
        setAppMenuOpen(false);
      }
    };
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") setAppMenuOpen(false);
    };

    document.addEventListener("pointerdown", closeOnOutsideClick);
    document.addEventListener("keydown", closeOnEscape);
    return () => {
      document.removeEventListener("pointerdown", closeOnOutsideClick);
      document.removeEventListener("keydown", closeOnEscape);
    };
  }, [appMenuOpen]);

  const mobileStore =
    device === "ios"
      ? {
          href: site.appStoreUrl,
          label: "Download Angel Granites on the App Store",
          icon: <AppleIcon />
        }
      : device === "android"
        ? {
            href: site.playStoreUrl,
            label: "Get Angel Granites on Google Play",
            icon: <AndroidIcon />
          }
        : device === "windows"
          ? {
              href: site.microsoftStoreUrl,
              label: "Get Angel Granites from the Microsoft Store",
              icon: <WindowsIcon />
            }
        : null;

  return (
    <aside
      ref={appLauncherRef}
      className="app-launcher"
      aria-label="Download the Angel Granites app"
    >
      {mobileStore ? (
        <a
          className="app-launcher__trigger"
          href={mobileStore.href}
          target="_blank"
          rel="noopener noreferrer"
          aria-label={mobileStore.label}
        >
          <span className="app-launcher__icon">{mobileStore.icon}</span>
          <span>Get the app</span>
        </a>
      ) : (
        <>
          <button
            className="app-launcher__trigger"
            type="button"
            aria-expanded={appMenuOpen}
            aria-controls="angel-app-download-options"
            onClick={() => setAppMenuOpen((current) => !current)}
          >
            <span className="app-launcher__icon">
              <PhoneIcon />
            </span>
            <span>Get the app</span>
          </button>
          <div
            id="angel-app-download-options"
            className="app-launcher__popover"
            hidden={!appMenuOpen}
          >
            <strong>Angel Granites app</strong>
            <span>Choose your device</span>
            <a
              className="app-launcher__option"
              href={site.appStoreUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="app-launcher__icon">
                <AppleIcon />
              </span>
              <span>
                <small>Download for</small>
                iPhone &amp; iPad
              </span>
            </a>
            <a
              className="app-launcher__option"
              href={site.playStoreUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="app-launcher__icon">
                <AndroidIcon />
              </span>
              <span>
                <small>Download for</small>
                Android
              </span>
            </a>
            <a
              className="app-launcher__option"
              href={site.microsoftStoreUrl}
              target="_blank"
              rel="noopener noreferrer"
            >
              <span className="app-launcher__icon">
                <WindowsIcon />
              </span>
              <span>
                <small>Download for</small>
                Windows
              </span>
            </a>
          </div>
        </>
      )}
    </aside>
  );
}
