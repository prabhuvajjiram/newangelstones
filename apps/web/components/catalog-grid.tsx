"use client";

import Link from "next/link";
import { useEffect, useMemo, useRef, useState } from "react";
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
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const lightboxTriggerRef = useRef<HTMLButtonElement | null>(null);

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

  const activeIndex = active
    ? filtered.findIndex((image) => image.src === active.src)
    : -1;

  function closeLightbox() {
    setActive(null);
    window.requestAnimationFrame(() => lightboxTriggerRef.current?.focus());
  }

  function moveLightbox(direction: -1 | 1) {
    if (activeIndex < 0 || filtered.length < 2) return;
    setActive(
      filtered[
        (activeIndex + direction + filtered.length) % filtered.length
      ]
    );
  }

  useEffect(() => {
    if (!active) return;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeButtonRef.current?.focus();

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        event.preventDefault();
        closeLightbox();
      } else if (event.key === "ArrowLeft") {
        event.preventDefault();
        moveLightbox(-1);
      } else if (event.key === "ArrowRight") {
        event.preventDefault();
        moveLightbox(1);
      }
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", handleKeyDown);
    };
  });

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
                onClick={(event) => {
                  lightboxTriggerRef.current = event.currentTarget;
                  setActive(image);
                }}
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
          onClick={closeLightbox}
        >
          <button
            ref={closeButtonRef}
            className="lightbox-close"
            type="button"
            onClick={closeLightbox}
            aria-label="Close image preview"
          >
            ×
          </button>
          <figure onClick={(event) => event.stopPropagation()}>
            <div className="lightbox-media">
              <img
                src={active.src}
                alt={`${active.name} granite monument design`}
              />
              {filtered.length > 1 ? (
                <nav
                  className="lightbox-image-nav"
                  aria-label={`Browse ${collectionName}`}
                >
                  <button
                    className="lightbox-arrow lightbox-arrow--previous"
                    type="button"
                    onClick={() => moveLightbox(-1)}
                    aria-label={`Previous image: ${
                      filtered[
                        (activeIndex - 1 + filtered.length) % filtered.length
                      ].name
                    }`}
                  >
                    <span aria-hidden="true">←</span>
                  </button>
                  <button
                    className="lightbox-arrow lightbox-arrow--next"
                    type="button"
                    onClick={() => moveLightbox(1)}
                    aria-label={`Next image: ${
                      filtered[(activeIndex + 1) % filtered.length].name
                    }`}
                  >
                    <span aria-hidden="true">→</span>
                  </button>
                </nav>
              ) : null}
            </div>
            <figcaption>
              <div>
                <span>
                  Design {activeIndex + 1} of {filtered.length}
                </span>
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
