"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import type { CatalogImage } from "@/data/catalog";
import { designHrefByImage } from "@/data/designs";
import {
  fetchLiveProductImages,
  productImageKey
} from "@/data/live-product-images";

export function CatalogGrid({
  images,
  collectionName,
  directory
}: {
  images: CatalogImage[];
  collectionName: string;
  directory: string;
}) {
  const [query, setQuery] = useState("");
  const [active, setActive] = useState<CatalogImage | null>(null);
  const [availableImages, setAvailableImages] = useState(images);

  useEffect(() => {
    const controller = new AbortController();
    void fetchLiveProductImages(directory, controller.signal)
      .then((liveImages) => {
        if (!liveImages.length) return;
        const merged = new Map(
          images.map((image) => [productImageKey(image.src), image])
        );
        for (const image of liveImages) {
          merged.set(productImageKey(image.src), {
            name: image.name,
            src: image.src
          });
        }
        setAvailableImages(
          [...merged.values()].sort((left, right) =>
            left.name.localeCompare(right.name, undefined, { numeric: true })
          )
        );
      })
      .catch(() => {
        // The static build-time image list remains the reliable fallback.
      });
    return () => controller.abort();
  }, [directory, images]);

  const filtered = useMemo(() => {
    const normalized = query.trim().toLowerCase();
    return normalized
      ? availableImages.filter((image) =>
          image.name.toLowerCase().includes(normalized)
        )
      : availableImages;
  }, [availableImages, query]);

  return (
    <>
      <div className="catalog-toolbar">
        <label>
          <span className="sr-only">Search {collectionName}</span>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
          </svg>
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder="Search by design number"
          />
        </label>
        <span>
          {filtered.length} {filtered.length === 1 ? "design" : "designs"}
        </span>
      </div>
      {filtered.length ? (
        <div className="product-grid">
          {filtered.map((image) => {
            const detailHref = designHrefByImage.get(image.src.split("?")[0]);
            const content = (
              <>
                <span className="product-image">
                  <img src={image.src} alt={`${image.name} granite monument design`} loading="lazy" />
                </span>
                <span>
                  <strong>{image.name}</strong>
                  <small>{detailHref ? "Design details" : "View design"}</small>
                </span>
              </>
            );

            return detailHref ? (
              <Link className="product-card product-card--detail" href={detailHref} key={image.src}>
                {content}
              </Link>
            ) : (
              <button
                className="product-card"
                type="button"
                key={image.src}
                onClick={() => setActive(image)}
                aria-label={`View ${image.name} larger`}
              >
                {content}
              </button>
            );
          })}
        </div>
      ) : (
        <div className="empty-state">
          <h2>No matching designs</h2>
          <p>Try a shorter design number or clear your search.</p>
          <button type="button" onClick={() => setQuery("")}>
            Clear search
          </button>
        </div>
      )}
      {active ? (
        <div
          className="lightbox"
          role="dialog"
          aria-modal="true"
          aria-label={`${active.name} image preview`}
          onClick={() => setActive(null)}
        >
          <button
            className="lightbox-close"
            type="button"
            onClick={() => setActive(null)}
            aria-label="Close image preview"
          >
            ×
          </button>
          <figure onClick={(event) => event.stopPropagation()}>
            <img src={active.src} alt={`${active.name} granite monument design`} />
            <figcaption>
              <div>
                <span>Design reference</span>
                <strong>{active.name}</strong>
              </div>
              <a href={`/contact/?design=${encodeURIComponent(active.name)}`}>
                Ask about this design
              </a>
            </figcaption>
          </figure>
        </div>
      ) : null}
    </>
  );
}
