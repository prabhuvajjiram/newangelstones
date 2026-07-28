"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  filterAndRankInventory,
  inventorySearchTerms
} from "@/data/inventory-search";
import type { InventoryProductImage } from "@/data/inventory-images";
import {
  fetchAllLiveProductImages,
  productImageKey
} from "@/data/live-product-images";

type InventoryRecord = Record<string, unknown>;

type InventoryResponse = {
  success?: boolean;
  data?: InventoryRecord[];
  Data?: InventoryRecord[];
  error?: string;
};

type DetailResponse = {
  success?: boolean;
  stones?: InventoryRecord[];
  error?: string;
};

type InventoryItem = {
  id: string;
  code: string;
  description: string;
  type: string;
  color: string;
  design: string;
  finish: string;
  size: string;
  location: string;
  quantity: string;
  designCode: string;
};

function field(record: InventoryRecord, ...names: string[]) {
  for (const name of names) {
    const exact = record[name];
    if (exact !== undefined && exact !== null && String(exact).trim()) {
      return String(exact).trim();
    }

    const matchingKey = Object.keys(record).find(
      (key) => key.toLowerCase() === name.toLowerCase()
    );
    if (matchingKey) {
      const value = record[matchingKey];
      if (value !== undefined && value !== null && String(value).trim()) {
        return String(value).trim();
      }
    }
  }
  return "";
}

function extractDesignCode(design: string, description: string) {
  const match = `${design} ${description}`.match(
    /\b(?:AG|AS|DF)-?\d+[A-Z]?(?:-\d+)?\b/i
  );
  return match?.[0].toUpperCase() ?? "";
}

function normalize(record: InventoryRecord, index: number): InventoryItem {
  const description = field(
    record,
    "EndProductDescription",
    "Description",
    "PDescription",
    "d"
  );
  const code = field(record, "EPCode", "EndProductCode", "ProductCode", "c");
  const design = field(record, "PDesign", "Design", "n");
  const color = field(record, "PColor", "Color", "g");
  const size = field(record, "Size", "PSize", "s");
  const location = field(
    record,
    "Locationname",
    "LocationName",
    "Location",
    "l"
  );

  return {
    id: `${code || description}-${location}-${index}`,
    code,
    description: description || "Granite inventory item",
    type: field(record, "Ptype", "PType", "ProductType", "t"),
    color,
    design,
    finish: field(record, "PFinish", "Finish", "f"),
    size,
    location,
    quantity: field(record, "Qty", "Quantity", "AvailableQty", "q") || "—",
    designCode: extractDesignCode(design, description)
  };
}

function unique(items: InventoryItem[], key: keyof InventoryItem) {
  return Array.from(new Set(items.map((item) => item[key]).filter(Boolean))).sort(
    (a, b) => a.localeCompare(b)
  );
}

function normalizedWords(value: string) {
  return value
    .toLowerCase()
    .replace(/\bgranite\b/g, "")
    .replace(/[^a-z0-9]+/g, " ")
    .trim();
}

function imagesForItem(
  item: InventoryItem,
  imageLookup: Map<string, InventoryProductImage[]>
) {
  const candidates = imageLookup.get(item.designCode) ?? [];
  const color = normalizedWords(item.color);
  const type = normalizedWords(item.type);
  return [...candidates].sort((left, right) => {
    const imageScore = (image: InventoryProductImage) => {
      const name = normalizedWords(image.name);
      const category = normalizedWords(image.category);
      return (
        (color && name.includes(color) ? 100 : 0) +
        (type && category.includes(type.replace(/s$/, "")) ? 20 : 0) -
        (image.isArchive ? 50 : 0)
      );
    };
    return imageScore(right) - imageScore(left);
  });
}

function InventoryThumbnail({
  item,
  image
}: {
  item: InventoryItem;
  image?: InventoryProductImage;
}) {
  const imageAlt = [
    item.designCode || item.design || item.code || item.description,
    item.color,
    item.type || "granite monument",
    "current inventory"
  ]
    .filter(Boolean)
    .join(" ");

  return (
    <span className="inventory-result-image">
      {image ? (
        <img
          src={image.thumbnailPath}
          alt={imageAlt}
          loading="lazy"
          width="220"
          height="220"
        />
      ) : (
        <span aria-hidden="true">{item.designCode || "Stock"}</span>
      )}
    </span>
  );
}

function DetailRow({ label, value }: { label: string; value: string }) {
  if (!value) return null;
  return (
    <div className="inventory-detail-row">
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  );
}

export function InventoryBrowser({
  imageIndex
}: {
  imageIndex: InventoryProductImage[];
}) {
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [query, setQuery] = useState("");
  const [type, setType] = useState("");
  const [color, setColor] = useState("");
  const [location, setLocation] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [selected, setSelected] = useState<InventoryItem | null>(null);
  const [stones, setStones] = useState<InventoryRecord[]>([]);
  const [detailImages, setDetailImages] = useState<InventoryProductImage[]>([]);
  const [detailLoading, setDetailLoading] = useState(false);
  const [detailError, setDetailError] = useState("");
  const closeButton = useRef<HTMLButtonElement>(null);
  const dialog = useRef<HTMLElement>(null);
  const returnFocus = useRef<HTMLButtonElement | null>(null);
  const pageSize = 48;

  const [liveImageIndex, setLiveImageIndex] = useState<InventoryProductImage[]>(
    []
  );
  const availableImageIndex = useMemo(() => {
    const merged = new Map(
      imageIndex.map((image) => [productImageKey(image.path), image])
    );
    for (const image of liveImageIndex) {
      merged.set(productImageKey(image.path), image);
    }
    return [...merged.values()];
  }, [imageIndex, liveImageIndex]);

  const imageLookup = useMemo(() => {
    const lookup = new Map<string, InventoryProductImage[]>();
    for (const image of availableImageIndex) {
      const current = lookup.get(image.code) ?? [];
      current.push(image);
      lookup.set(image.code, current);
    }
    return lookup;
  }, [availableImageIndex]);

  useEffect(() => {
    const controller = new AbortController();
    void fetchAllLiveProductImages(controller.signal)
      .then((images) => {
        const designCode = /(?:AG|AS|DF)[\s-]?\d+[A-Z]?(?:[\s-]\d+)?/i;
        setLiveImageIndex(
          images.flatMap((image) => {
            const match = image.name.match(designCode);
            if (!match) return [];
            return [
              {
                code: match[0].replace(/\s+/g, "-").toUpperCase(),
                name: image.name,
                path: image.src,
                thumbnailPath: image.src,
                category: image.category,
                isArchive: false
              }
            ];
          })
        );
      })
      .catch(() => {
        // Inventory continues to use the packaged image index if the live
        // directory helper is unavailable.
      });
    return () => controller.abort();
  }, []);

  const loadInventory = useCallback(async () => {
    setLoading(true);
    setError("");

    try {
      const apiPageSize = 1000;
      const maximumApiPages = 50;
      const records: InventoryRecord[] = [];

      for (let apiPage = 1; apiPage <= maximumApiPages; apiPage += 1) {
        const parameters = new URLSearchParams({
          page: String(apiPage),
          pageSize: String(apiPageSize),
          format: "compact"
        });
        const response = await fetch(`/inventory-proxy.php?${parameters}`, {
          headers: { Accept: "application/json" },
          cache: "no-store"
        });
        const payload = (await response.json()) as InventoryResponse;

        if (!response.ok || payload.success === false) {
          throw new Error(
            payload.error || "Inventory service is temporarily unavailable."
          );
        }

        const pageRecords = payload.data ?? payload.Data;
        if (!Array.isArray(pageRecords)) {
          throw new Error("Inventory service returned an unexpected response.");
        }
        records.push(...pageRecords);
        if (pageRecords.length < apiPageSize) break;
        if (apiPage === maximumApiPages) {
          throw new Error(
            "Inventory is larger than the current safe loading limit. Please contact us for assistance."
          );
        }
      }

      setItems(records.map(normalize));
    } catch (requestError) {
      setItems([]);
      setError(
        requestError instanceof Error
          ? requestError.message
          : "Inventory service is temporarily unavailable."
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void loadInventory();
  }, [loadInventory]);

  useEffect(() => {
    const search = new URLSearchParams(window.location.search).get("search");
    if (search) setQuery(search);
  }, []);

  const options = useMemo(
    () => ({
      types: unique(items, "type"),
      colors: unique(items, "color"),
      locations: unique(items, "location")
    }),
    [items]
  );

  const visibleItems = useMemo(() => {
    const ranked = filterAndRankInventory(items, { query, type, color, location });
    if (inventorySearchTerms(query)) return ranked;
    return [...ranked].sort(
      (left, right) =>
        Number(imagesForItem(right, imageLookup).length > 0) -
        Number(imagesForItem(left, imageLookup).length > 0)
    );
  }, [items, query, type, color, location, imageLookup]);

  useEffect(() => {
    setCurrentPage(1);
  }, [query, type, color, location]);

  const closeDetails = useCallback(() => {
    setSelected(null);
    setStones([]);
    setDetailImages([]);
    setDetailError("");
    window.requestAnimationFrame(() => returnFocus.current?.focus());
  }, []);

  useEffect(() => {
    if (!selected) return;
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        closeDetails();
        return;
      }
      if (event.key !== "Tab" || !dialog.current) return;
      const focusable = [
        ...dialog.current.querySelectorAll<HTMLElement>(
          'a[href], button:not([disabled]), select:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )
      ];
      if (!focusable.length) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    window.addEventListener("keydown", onKeyDown);
    closeButton.current?.focus();
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", onKeyDown);
    };
  }, [selected, closeDetails]);

  const openDetails = useCallback(async (
    item: InventoryItem,
    trigger?: HTMLButtonElement
  ) => {
    const itemImages = imagesForItem(item, imageLookup);
    returnFocus.current = trigger ?? null;
    setSelected(item);
    setStones([]);
    setDetailImages(itemImages);
    setDetailError("");
    setDetailLoading(true);

    try {
      const details = new URLSearchParams({
        action: "getDetails",
        epcode: item.code
      });
      const detailResponse = await fetch(`/inventory-proxy.php?${details}`, {
        headers: { Accept: "application/json" },
        cache: "no-store"
      });
      const payload = (await detailResponse.json()) as DetailResponse;
      if (!detailResponse.ok || payload.success === false) {
        throw new Error(payload.error || "Detailed stock records are unavailable.");
      }
      const allStones = Array.isArray(payload.stones) ? payload.stones : [];
      const locationStones = allStones.filter(
        (stone) =>
          field(stone, "LocationName", "Location").toLowerCase() ===
          item.location.toLowerCase()
      );
      setStones(locationStones.length ? locationStones : allStones);
    } catch (requestError) {
      setDetailError(
        requestError instanceof Error
          ? requestError.message
          : "Detailed stock records are unavailable."
      );
    } finally {
      setDetailLoading(false);
    }
  }, [imageLookup]);

  const totalPages = Math.max(1, Math.ceil(visibleItems.length / pageSize));
  const pageStart = (currentPage - 1) * pageSize;
  const pageItems = visibleItems.slice(pageStart, pageStart + pageSize);

  return (
    <div className="inventory-browser">
      <div className="inventory-toolbar" aria-label="Inventory filters">
        <label className="inventory-search">
          <span>Search inventory</span>
          <input
            type="search"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder='Try “AG-298”, “heart headstone” or “4-0 x 2-4 x 0-8”'
          />
        </label>
        <label>
          <span>Product type</span>
          <select value={type} onChange={(event) => setType(event.target.value)}>
            <option value="">All types</option>
            {options.types.map((value) => (
              <option key={value}>{value}</option>
            ))}
          </select>
        </label>
        <label>
          <span>Granite color</span>
          <select value={color} onChange={(event) => setColor(event.target.value)}>
            <option value="">All colors</option>
            {options.colors.map((value) => (
              <option key={value}>{value}</option>
            ))}
          </select>
        </label>
        <label>
          <span>Location</span>
          <select
            value={location}
            onChange={(event) => setLocation(event.target.value)}
          >
            <option value="">All locations</option>
            {options.locations.map((value) => (
              <option key={value}>{value}</option>
            ))}
          </select>
        </label>
      </div>

      {loading ? (
        <div className="inventory-loading" role="status">
          <span className="inventory-loading-status">
            <span className="inventory-spinner" aria-hidden="true" />
            Loading current inventory…
          </span>
          <div
            className="inventory-results inventory-loading-grid"
            aria-hidden="true"
          >
            {Array.from({ length: 12 }, (_, index) => (
              <span className="inventory-loading-card" key={index}>
                <span className="inventory-loading-lines" />
              </span>
            ))}
          </div>
        </div>
      ) : error ? (
        <div className="inventory-state inventory-state--error" role="alert">
          <strong>We could not load current inventory.</strong>
          <p>{error}</p>
          <button className="button button--gold" type="button" onClick={loadInventory}>
            Try again
          </button>
        </div>
      ) : (
        <>
          <div className="inventory-summary" aria-live="polite">
            <strong>{visibleItems.length}</strong>{" "}
            {visibleItems.length === 1 ? "inventory item" : "inventory items"}
            {visibleItems.length !== items.length ? ` matching ${items.length} total` : ""}
            {visibleItems.length > 0
              ? ` · Showing ${pageStart + 1}–${Math.min(
                  pageStart + pageSize,
                  visibleItems.length
                )}`
              : ""}
          </div>
          {visibleItems.length ? (
            <>
              <div className="inventory-results">
                {pageItems.map((item) => (
                  <button
                    className="inventory-result"
                    type="button"
                    key={item.id}
                    onClick={(event) =>
                      void openDetails(item, event.currentTarget)
                    }
                    aria-label={`View details for ${item.description}`}
                  >
                    <InventoryThumbnail
                      item={item}
                      image={imagesForItem(item, imageLookup)[0]}
                    />
                    <span className="inventory-result-copy">
                      <span className="inventory-result-topline">
                        <span>{item.design || item.type || "Granite stock"}</span>
                        <b>{item.quantity} available</b>
                      </span>
                      <strong title={item.description}>{item.description}</strong>
                      <span className="inventory-result-specs">
                        <span><small>Color</small>{item.color || "—"}</span>
                        <span><small>Size</small>{item.size || "—"}</span>
                        <span><small>Finish</small>{item.finish || "—"}</span>
                        <span><small>Location</small>{item.location || "—"}</span>
                      </span>
                      <span className="inventory-result-action">
                        View crate and container details <span aria-hidden="true">→</span>
                      </span>
                    </span>
                  </button>
                ))}
              </div>
              {totalPages > 1 ? (
                <nav className="inventory-pagination" aria-label="Inventory pages">
                  <button
                    type="button"
                    onClick={() => setCurrentPage((page) => Math.max(1, page - 1))}
                    disabled={currentPage === 1}
                  >
                    Previous
                  </button>
                  <span>
                    Page <strong>{currentPage}</strong> of {totalPages}
                  </span>
                  <button
                    type="button"
                    onClick={() =>
                      setCurrentPage((page) => Math.min(totalPages, page + 1))
                    }
                    disabled={currentPage === totalPages}
                  >
                    Next
                  </button>
                </nav>
              ) : null}
            </>
          ) : (
            <div className="inventory-state">
              <strong>No inventory matches those filters.</strong>
              <p>Try a broader search or clear one of the selected filters.</p>
            </div>
          )}
        </>
      )}

      {selected ? (
        <div className="inventory-dialog-backdrop" onMouseDown={closeDetails}>
          <section
            ref={dialog}
            className="inventory-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="inventory-dialog-title"
            onMouseDown={(event) => event.stopPropagation()}
          >
            <header>
              <div>
                <span>{selected.design || selected.type}</span>
                <h2 id="inventory-dialog-title">{selected.description}</h2>
              </div>
              <button
                ref={closeButton}
                className="inventory-dialog-close"
                type="button"
                onClick={closeDetails}
                aria-label="Close inventory details"
              >
                ×
              </button>
            </header>

            <div className="inventory-dialog-content">
              <dl className="inventory-detail-summary">
                <DetailRow label="Product type" value={selected.type} />
                <DetailRow label="Granite color" value={selected.color} />
                <DetailRow label="Design" value={selected.design} />
                <DetailRow label="Finish" value={selected.finish} />
                <DetailRow label="Size" value={selected.size} />
                <DetailRow label="Location" value={selected.location} />
                <DetailRow label="Total available" value={selected.quantity} />
                <DetailRow label="Product code" value={selected.code} />
              </dl>

              {detailImages[0] ? (
                <figure className="inventory-detail-primary">
                  <a
                    href={detailImages[0].path}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    <img
                      src={detailImages[0].path}
                      alt={
                        detailImages[0].name ||
                        selected.design ||
                        selected.description
                      }
                    />
                  </a>
                  <figcaption>
                    <strong>Design reference image</strong>
                    Photography may show a different granite color. Confirm the
                    selected color and individual stone details before ordering.
                  </figcaption>
                </figure>
              ) : null}

              {detailLoading ? (
                <div className="inventory-detail-loading" role="status">
                  <span className="inventory-spinner" aria-hidden="true" />
                  Loading individual stone records…
                </div>
              ) : detailError ? (
                <div className="inventory-detail-message" role="alert">
                  <strong>Detailed records could not be loaded.</strong>
                  <p>{detailError}</p>
                </div>
              ) : stones.length ? (
                <section className="inventory-stones" aria-labelledby="stone-records-title">
                  <h3 id="stone-records-title">
                    Individual stones <span>{stones.length}</span>
                  </h3>
                  <div>
                    {stones.map((stone, index) => (
                      <article key={`${field(stone, "StockId")}-${index}`}>
                        <h4>Stone {index + 1}</h4>
                        <dl>
                          <DetailRow label="Container" value={field(stone, "Container")} />
                          <DetailRow label="Crate number" value={field(stone, "CrateNo")} />
                          <DetailRow label="Status" value={field(stone, "Status")} />
                          <DetailRow label="Stock ID" value={field(stone, "StockId")} />
                          <DetailRow label="Location" value={field(stone, "LocationName")} />
                          <DetailRow
                            label="Sublocation"
                            value={field(stone, "SublocationName")}
                          />
                          <DetailRow
                            label="Weight"
                            value={
                              field(stone, "Weight")
                                ? `${field(stone, "Weight")} lbs`
                                : ""
                            }
                          />
                          <DetailRow label="Notes" value={field(stone, "Comments")} />
                        </dl>
                      </article>
                    ))}
                  </div>
                </section>
              ) : (
                <div className="inventory-detail-message">
                  Individual crate records are not available for this item.
                </div>
              )}

              {detailImages.length > 1 ? (
                <section className="inventory-detail-images" aria-labelledby="item-images-title">
                  <h3 id="item-images-title">Additional product images</h3>
                  <div>
                    {detailImages.slice(1).map((image) => (
                      <a
                        key={image.path}
                        href={image.path}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        <img
                          src={image.path}
                          alt={image.name || selected.design || selected.description}
                          loading="lazy"
                        />
                      </a>
                    ))}
                  </div>
                </section>
              ) : null}
            </div>
          </section>
        </div>
      ) : null}
    </div>
  );
}
