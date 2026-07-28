"use client";

import { useEffect, useState } from "react";

type ThemePreference = "system" | "dark" | "light";
type EffectiveTheme = "dark" | "light";

function applyTheme(preference: ThemePreference): EffectiveTheme {
  const effective: EffectiveTheme =
    preference === "system"
      ? window.matchMedia("(prefers-color-scheme: light)").matches
        ? "light"
        : "dark"
      : preference;
  document.documentElement.dataset.theme = effective;
  document.documentElement.dataset.themePreference = preference;
  document.documentElement.style.colorScheme = effective;
  return effective;
}

export function ThemeSelector() {
  const [preference, setPreference] = useState<ThemePreference>("dark");
  const [effectiveTheme, setEffectiveTheme] =
    useState<EffectiveTheme>("dark");

  useEffect(() => {
    const saved = window.localStorage.getItem("angel-theme");
    const initial: ThemePreference =
      saved === "dark" || saved === "light" || saved === "system"
        ? saved
        : "dark";
    setPreference(initial);
    setEffectiveTheme(applyTheme(initial));

    const media = window.matchMedia("(prefers-color-scheme: light)");
    const onSystemChange = () => {
      if (
        (window.localStorage.getItem("angel-theme") ?? "dark") === "system"
      ) {
        setEffectiveTheme(applyTheme("system"));
      }
    };
    media.addEventListener("change", onSystemChange);
    return () => media.removeEventListener("change", onSystemChange);
  }, []);

  const update = (next: ThemePreference) => {
    setPreference(next);
    window.localStorage.setItem("angel-theme", next);
    setEffectiveTheme(applyTheme(next));
  };

  const title =
    preference === "system"
      ? `Theme: System (${effectiveTheme})`
      : `Theme: ${preference}`;

  return (
    <label className="theme-selector" title={title}>
      <span className="theme-selector__icon" aria-hidden="true">
        {effectiveTheme === "dark" ? (
          <svg viewBox="0 0 24 24" focusable="false">
            <path d="M20.4 15.3A8.5 8.5 0 0 1 8.7 3.6 8.5 8.5 0 1 0 20.4 15.3Z" />
          </svg>
        ) : (
          <svg viewBox="0 0 24 24" focusable="false">
            <circle cx="12" cy="12" r="3.5" />
            <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
          </svg>
        )}
      </span>
      <select
        aria-label="Color theme"
        title={title}
        value={preference}
        onChange={(event) => update(event.target.value as ThemePreference)}
      >
        <option value="system">System theme</option>
        <option value="light">Light theme</option>
        <option value="dark">Dark theme</option>
      </select>
    </label>
  );
}
