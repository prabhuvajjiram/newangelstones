"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import type {
  GraniteColor,
  GraniteColorFamily,
  GraniteColorMaterial
} from "@/data/catalog";
import { fetchLiveColorImages } from "@/data/live-color-images";

type DisplayColor = GraniteColor & {
  hasDetailPage: boolean;
};

const retiredLiveColorKeys = new Set(["picasso"]);
const catalogContextKey = "angel-color-catalog-context";
const restoreRequestKey = "angel-color-catalog-restore";

function colorKey(value: string): string {
  const normalized = value
    .toLowerCase()
    .replace(/\b(?:granite|quartzite|marble|sandstone)\b/g, "")
    .replace(/\bcat(?:['’]?s)?\s+eye\b/g, "cats eye")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");
  if (normalized === "bluepearl") return "blue-pearl";
  if (normalized === "pacific-grey") return "pacific-gray";
  if (normalized === "silk-blue") return "blue-silk";
  return normalized;
}

function liveMaterial(name: string): GraniteColorMaterial {
  const normalized = name.toLowerCase();
  if (normalized.includes("quartzite")) return "Quartzite";
  if (normalized.includes("marble")) return "Marble";
  if (normalized.includes("sandstone")) return "Sandstone";
  return "Granite";
}

function liveFamily(name: string): GraniteColorFamily {
  const normalized = name.toLowerCase();
  if (/(green|olive)/.test(normalized)) return "Green";
  if (/(black|ebony|galaxy)/.test(normalized)) return "Black";
  if (/(gray|grey|barre|silver|white|mist|impala|marble)/.test(normalized)) {
    return "Gray";
  }
  if (/(blue|pearl|opal)/.test(normalized)) return "Blue";
  if (
    /(red|pink|rose|rubin|strawberry|adhoni|romantica|halmstad|lila)/.test(
      normalized
    )
  ) {
    return "Red & Pink";
  }
  return "Brown & Multicolor";
}

export function ColorGrid({ colors }: { colors: GraniteColor[] }) {
  const [query, setQuery] = useState("");
  const [availableColors, setAvailableColors] = useState<DisplayColor[]>(
    colors.map((color) => ({ ...color, hasDetailPage: true }))
  );

  useEffect(() => {
    if (window.sessionStorage.getItem(restoreRequestKey) !== "1") return;
    window.sessionStorage.removeItem(restoreRequestKey);

    const storedContext = window.sessionStorage.getItem(catalogContextKey);
    window.sessionStorage.removeItem(catalogContextKey);
    if (!storedContext) return;

    try {
      const context = JSON.parse(storedContext) as {
        query?: string;
        scrollY?: number;
        savedAt?: number;
      };
      if (
        typeof context.savedAt !== "number" ||
        Date.now() - context.savedAt > 30 * 60 * 1000
      ) {
        return;
      }
      if (typeof context.query === "string") setQuery(context.query);
      if (typeof context.scrollY === "number") {
        window.requestAnimationFrame(() => {
          window.requestAnimationFrame(() => {
            window.scrollTo({ top: context.scrollY });
          });
        });
      }
    } catch {
      // Invalid session context should never prevent the color catalog loading.
    }
  }, []);

  useEffect(() => {
    const controller = new AbortController();
    void fetchLiveColorImages(controller.signal)
      .then((liveColors) => {
        const merged = new Map<string, DisplayColor>(
          colors.map((color) => [
            colorKey(color.name),
            { ...color, hasDetailPage: true }
          ])
        );
        for (const liveColor of liveColors) {
          const key = colorKey(liveColor.name);
          if (retiredLiveColorKeys.has(key)) continue;
          const existing = merged.get(key);
          if (existing) {
            merged.set(key, { ...existing, image: liveColor.src });
            continue;
          }

          const material = liveMaterial(liveColor.name);
          const name = liveColor.name.toLowerCase().endsWith(material.toLowerCase())
            ? liveColor.name
            : `${liveColor.name} ${material}`;
          const family = liveFamily(name);
          merged.set(key, {
            slug: material === "Granite" ? `${key}-granite` : key,
            name,
            description: `${name} is a live natural-stone color reference. Confirm current monument material and inventory with Angel Granites.`,
            image: liveColor.src,
            sku: "Live color",
            material,
            family,
            searchTerms: [name, family, material],
            hasDetailPage: false
          });
        }
        setAvailableColors(
          [...merged.values()].sort((left, right) =>
            left.name.localeCompare(right.name, undefined, { numeric: true })
          )
        );
      })
      .catch(() => {
        // The complete build-time color catalog remains available if the
        // preserved live color endpoint cannot be reached.
      });
    return () => controller.abort();
  }, [colors]);

  const filtered = useMemo(() => {
    const normalized = query.trim().toLowerCase();
    return normalized
      ? availableColors.filter((color) =>
          `${color.name} ${color.description} ${color.family} ${color.searchTerms.join(" ")}`
            .toLowerCase()
            .includes(normalized)
        )
      : availableColors;
  }, [availableColors, query]);

  return (
    <>
      <div className="catalog-toolbar">
        <label>
          <span className="sr-only">Search granite colors</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
          </svg>
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search granite colors"
          />
        </label>
        <span>{filtered.length} colors</span>
      </div>
      <div className="colors-grid">
        {filtered.map((color) => {
          const href = color.hasDetailPage
            ? `/colors/${color.slug}/`
            : `/inventory/?search=${encodeURIComponent(color.name)}`;
          return (
            <Link
              href={href}
              className="color-card"
              key={color.slug}
              onClick={() => {
                if (!color.hasDetailPage) return;
                window.sessionStorage.setItem(
                  catalogContextKey,
                  JSON.stringify({
                    query,
                    scrollY: window.scrollY,
                    savedAt: Date.now()
                  })
                );
              }}
            >
              <span className="color-card-image">
                <img
                  src={color.image}
                  alt={`${color.name} polished stone sample`}
                  loading="lazy"
                />
              </span>
              <span className="color-card-copy">
                <small>{color.sku}</small>
                <strong>{color.name}</strong>
                <span>
                  {color.hasDetailPage
                    ? "View color details"
                    : "Search current inventory"}
                </span>
              </span>
            </Link>
          );
        })}
      </div>
    </>
  );
}
