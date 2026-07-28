export type SearchableInventoryItem = {
  code: string;
  description: string;
  size: string;
  design: string;
  color: string;
  type: string;
  finish: string;
  location: string;
  quantity: string;
};

const spokenSearchFillers = new Set([
  "a",
  "all",
  "any",
  "are",
  "available",
  "availability",
  "can",
  "could",
  "do",
  "does",
  "find",
  "for",
  "full",
  "have",
  "in",
  "inventory",
  "is",
  "me",
  "of",
  "please",
  "search",
  "show",
  "stock",
  "stone",
  "stones",
  "the",
  "there",
  "thick",
  "thickness",
  "with",
  "you"
]);

export function normalizeInventoryQuery(value: string) {
  return value
    .toLowerCase()
    .replaceAll("×", "x")
    .replace(/[^a-z0-9]+/g, " ")
    .replace(/(\d)x(\d)/g, "$1 x $2")
    .trim()
    .replace(/\s+/g, " ");
}

export function inventorySearchTerms(value: string) {
  const normalized = normalizeInventoryQuery(
    value.replace(/\bhead\s+stones?\b/gi, "headstone")
  );
  if (!normalized) return "";

  const terms: string[] = normalized
    .split(" ")
    .filter((term) => term === "x" || !spokenSearchFillers.has(term));
  const headstoneIndex = terms.indexOf("headstone");
  const hadHeadstone = headstoneIndex !== -1;
  if (hadHeadstone) terms.splice(headstoneIndex, 1);
  const usefulTermCount = terms.filter((term) => term !== "x").length;
  if (hadHeadstone && usefulTermCount === 0) {
    terms.push("tablet");
  }
  return terms.join(" ").trim();
}

function compact(value: string) {
  return value.replace(/[^a-z0-9]/g, "");
}

function dimensionSignature(value: string) {
  const parts = value.split("x").map((part) => part.trim());
  if (parts.length !== 3 || parts.some((part) => !/\d/.test(part))) return null;
  return parts.map(compact).sort().join("|");
}

function removeMatch(value: string, match: RegExpMatchArray) {
  if (match.index === undefined) return value;
  return `${value.slice(0, match.index)} ${value.slice(
    match.index + match[0].length
  )}`
    .trim()
    .replace(/\s+/g, " ");
}

function fullDimensionMatch(value: string) {
  return value.match(
    /\b\d+(?:\s+\d+)?\s+x\s+\d+(?:\s+\d+)?\s+x\s+\d+(?:\s+\d+)?\b/
  );
}

function dimensionPairMatches(value: string) {
  if (value.includes("x")) return [];
  return [...value.matchAll(/\b(\d{1,2})\s+(\d{1,2})\b/g)];
}

function designIdentifierMatch(value: string) {
  return value.match(/\b(?:ag|as|df)\s+\d+[a-z]?(?:\s+\d+)?\b/);
}

function sizeComponents(value: string) {
  return new Set(
    normalizeInventoryQuery(value)
      .split("x")
      .map((component) => compact(component.trim()))
      .filter(Boolean)
  );
}

type DimensionComponent = {
  value: string;
  complete: boolean;
};

function partialDimensionQuery(value: string): DimensionComponent[] | null {
  if (!value.includes("x")) return null;
  const parts = value.split("x");
  if (parts.length > 3) return null;

  const components: DimensionComponent[] = [];
  for (let index = 0; index < parts.length; index += 1) {
    const part = parts[index].trim();
    if (!part) {
      if (index === parts.length - 1) continue;
      return null;
    }
    const match = part.match(/^(\d+)(?:\s+(\d+))?$/);
    if (!match) return null;
    components.push({ value: compact(part), complete: Boolean(match[2]) });
  }
  return components.length ? components : null;
}

function matchesPartialDimensions(
  size: string,
  queryComponents: DimensionComponent[]
) {
  const remaining = [...sizeComponents(size)];
  for (const queryComponent of queryComponents) {
    const index = remaining.findIndex((sizeComponent) =>
      queryComponent.complete
        ? sizeComponent === queryComponent.value
        : sizeComponent.startsWith(queryComponent.value)
    );
    if (index === -1) return false;
    remaining.splice(index, 1);
  }
  return true;
}

function score(item: SearchableInventoryItem, query: string) {
  if (!query) return 1000;
  const normalizedSize = normalizeInventoryQuery(item.size);
  const storedDimensions = dimensionSignature(normalizedSize);
  let semanticQuery = query;
  let rankingAdjustment = 0;

  const fullDimensions = fullDimensionMatch(query);
  if (fullDimensions) {
    const queryDimensions = dimensionSignature(fullDimensions[0]);
    if (!queryDimensions || queryDimensions !== storedDimensions) return null;
    rankingAdjustment -=
      compact(normalizedSize) === compact(fullDimensions[0]) ? 260 : 240;
    semanticQuery = removeMatch(semanticQuery, fullDimensions);
  } else {
    const dimensionPairs = dimensionPairMatches(query);
    if (dimensionPairs.length) {
      const storedComponents = sizeComponents(item.size);
      if (
        dimensionPairs.some(
          (match) => !storedComponents.has(compact(match[0]))
        )
      ) {
        return null;
      }
      rankingAdjustment -= 160 * dimensionPairs.length;
      for (const pair of [...dimensionPairs].reverse()) {
        semanticQuery = removeMatch(semanticQuery, pair);
      }
    }
  }

  const designIdentifier = designIdentifierMatch(semanticQuery);
  if (designIdentifier) {
    const compactIdentifier = compact(designIdentifier[0]);
    const designFields = [item.design, item.code, item.description].map((value) =>
      compact(normalizeInventoryQuery(value))
    );
    if (!designFields.some((value) => value.includes(compactIdentifier))) {
      return null;
    }
    rankingAdjustment -= 300;
    semanticQuery = removeMatch(semanticQuery, designIdentifier);
  }

  semanticQuery = semanticQuery.trim();
  if (!semanticQuery) return rankingAdjustment;

  const compactQuery = compact(semanticQuery);
  if (
    /^\d+\s+\d+$/.test(semanticQuery) &&
    sizeComponents(item.size).has(compactQuery)
  ) {
    return rankingAdjustment + 8;
  }

  const partialDimensions = partialDimensionQuery(semanticQuery);
  if (partialDimensions) {
    if (!matchesPartialDimensions(item.size, partialDimensions)) return null;
    return (
      rankingAdjustment +
      (partialDimensions.every((component) => component.complete) ? 10 : 15)
    );
  }

  const fields = [
    [item.size, 0],
    [item.code, 10],
    [item.design, 20],
    [item.color, 30],
    [item.type, 40],
    [item.finish, 50],
    [item.description, 60],
    [item.location, 70]
  ] as const;

  let bestScore: number | null = null;
  for (const [rawValue, priority] of fields) {
    const value = normalizeInventoryQuery(rawValue);
    if (!value) continue;
    const compactValue = compact(value);
    let fieldScore: number | null = null;
    if (value === semanticQuery || compactValue === compactQuery) {
      fieldScore = priority;
    } else if (
      value.startsWith(semanticQuery) ||
      compactValue.startsWith(compactQuery)
    ) {
      fieldScore = 100 + priority;
    } else if (
      value.includes(semanticQuery) ||
      compactValue.includes(compactQuery)
    ) {
      fieldScore = 200 + priority;
    }
    if (fieldScore !== null && (bestScore === null || fieldScore < bestScore)) {
      bestScore = fieldScore;
    }
  }
  if (bestScore !== null) return rankingAdjustment + bestScore;

  const tokens = new Set(
    semanticQuery.split(" ").filter((token) => token && token !== "x")
  );
  const searchableTokens = new Set(
    fields
      .map(([value]) => normalizeInventoryQuery(value))
      .filter(Boolean)
      .join(" ")
      .split(" ")
  );
  if (
    tokens.size &&
    [...tokens].every((token) =>
      [...searchableTokens].some((candidate) => candidate.startsWith(token))
    )
  ) {
    return rankingAdjustment + 400;
  }
  return null;
}

function exactFilter(value: string, selected: string) {
  return !selected || normalizeInventoryQuery(value) === normalizeInventoryQuery(selected);
}

export function filterAndRankInventory<T extends SearchableInventoryItem>(
  items: T[],
  filters: {
    query?: string;
    type?: string;
    color?: string;
    location?: string;
  }
) {
  const query = inventorySearchTerms(filters.query ?? "");
  return items
    .map((item) => ({ item, score: score(item, query) }))
    .filter(
      (
        match
      ): match is {
        item: T;
        score: number;
      } =>
        match.score !== null &&
        exactFilter(match.item.type, filters.type ?? "") &&
        exactFilter(match.item.color, filters.color ?? "") &&
        exactFilter(match.item.location, filters.location ?? "")
    )
    .sort((left, right) => {
      if (left.score !== right.score) return left.score - right.score;
      const quantityDifference =
        Number.parseFloat(right.item.quantity) - Number.parseFloat(left.item.quantity);
      if (Number.isFinite(quantityDifference) && quantityDifference !== 0) {
        return quantityDifference;
      }
      const descriptionDifference = left.item.description.localeCompare(
        right.item.description,
        undefined,
        { sensitivity: "base" }
      );
      return descriptionDifference || left.item.code.localeCompare(right.item.code);
    })
    .map((match) => match.item);
}
