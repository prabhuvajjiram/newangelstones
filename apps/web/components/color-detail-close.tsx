"use client";

import Link from "next/link";
import { useCallback, useEffect } from "react";
import { useRouter } from "next/navigation";

const restoreRequestKey = "angel-color-catalog-restore";
const catalogContextKey = "angel-color-catalog-context";

export function ColorDetailClose() {
  const router = useRouter();
  const returnToColors = useCallback(() => {
    const hasCatalogContext =
      window.sessionStorage.getItem(catalogContextKey) !== null;

    if (hasCatalogContext) {
      window.sessionStorage.setItem(restoreRequestKey, "1");
      router.back();
      window.setTimeout(() => {
        window.sessionStorage.removeItem(restoreRequestKey);
      }, 1500);
      return;
    }

    router.replace("/granite-colors/#color-catalog");
  }, [router]);

  useEffect(() => {
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key !== "Escape" || event.defaultPrevented) return;
      event.preventDefault();
      returnToColors();
    };

    window.addEventListener("keydown", closeOnEscape);
    return () => window.removeEventListener("keydown", closeOnEscape);
  }, [returnToColors]);

  return (
    <Link
      className="color-detail-close"
      href="/granite-colors/#color-catalog"
      aria-label="Close color detail and return to granite colors"
      title="Close and return to granite colors"
      replace
      onClick={(event) => {
        event.preventDefault();
        returnToColors();
      }}
    >
      <span aria-hidden="true">×</span>
      <span>Close</span>
    </Link>
  );
}
