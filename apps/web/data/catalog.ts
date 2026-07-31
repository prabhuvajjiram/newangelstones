import fs from "node:fs";
import path from "node:path";

const repositoryRoot = path.resolve(process.cwd(), "../..");
const imageExtensions = new Set([".jpg", ".jpeg", ".png", ".webp"]);

type FeaturedSource = {
  id: string;
  name: string;
  description: string;
  image: string;
  label: string;
};

export type CatalogImage = {
  name: string;
  src: string;
};

export type CatalogCollection = {
  slug: string;
  directory: string;
  name: string;
  eyebrow: string;
  description: string;
  images: CatalogImage[];
  guide: {
    title: string;
    introduction: string;
    items: Array<{ title: string; description: string }>;
  };
};

export type GraniteColor = {
  slug: string;
  name: string;
  description: string;
  image: string;
  sku: string;
  material: GraniteColorMaterial;
  family: GraniteColorFamily;
  searchTerms: string[];
};

export type GraniteColorMaterial =
  | "Granite"
  | "Quartzite"
  | "Marble"
  | "Sandstone";

export type GraniteColorFamily =
  | "Black"
  | "Gray"
  | "Blue"
  | "Green"
  | "Red & Pink"
  | "Brown & Multicolor";

export type FeaturedCollection = FeaturedSource & {
  href: string;
  imagePath: string;
};

function readJson<T>(relativePath: string): T {
  return JSON.parse(
    fs.readFileSync(path.join(repositoryRoot, relativePath), "utf8")
  ) as T;
}

function publicPathFromAbsoluteUrl(url: string): string {
  return decodeURIComponent(new URL(url).pathname);
}

function diskPathFromPublicPath(publicPath: string): string {
  return path.join(repositoryRoot, publicPath.replace(/^\/+/, ""));
}

function slugify(value: string): string {
  return value
    .toLowerCase()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");
}

function humanizeFilename(filename: string): string {
  return filename
    .replace(/\.[^.]+$/, "")
    .replace(/[_-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function titleCase(value: string): string {
  return value.replace(/\b[a-z]/g, (letter) => letter.toUpperCase());
}

function selectPreferredImages(directory: string): CatalogImage[] {
  const absoluteDirectory = path.join(repositoryRoot, "images/products", directory);
  const files = fs
    .readdirSync(absoluteDirectory)
    .filter((file) => imageExtensions.has(path.extname(file).toLowerCase()))
    .filter((file) => fs.statSync(path.join(absoluteDirectory, file)).size > 0)
    .sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));

  const byStem = new Map<string, string>();
  for (const file of files) {
    const stem = file.replace(/\.[^.]+$/, "").toLowerCase();
    const current = byStem.get(stem);
    const isWebp = path.extname(file).toLowerCase() === ".webp";
    if (!current || isWebp) {
      byStem.set(stem, file);
    }
  }

  return [...byStem.values()].map((file) => ({
    name: humanizeFilename(file),
    src: `/images/products/${directory}/${file}`
  }));
}

const collectionDefinitions = [
  {
    slug: "monuments",
    directory: "Monuments",
    name: "In-stock Granite Monuments",
    eyebrow: "Ready to ship",
    description:
      "Browse in-stock upright monuments, slants, bevels, markers and memorial designs available from Elberton, Georgia."
  },
  {
    slug: "mbna-2025",
    directory: "MBNA_2025",
    name: "MBNA Monument Collection",
    eyebrow: "Dealer collection",
    description:
      "Explore the MBNA monument collection with design references ready for availability and wholesale pricing requests."
  },
  {
    slug: "benches",
    directory: "Benches",
    name: "Granite Memorial Benches",
    eyebrow: "Memorial seating",
    description:
      "Granite benches for cemeteries, gardens and lasting family memorials, available in multiple designs and colors."
  },
  {
    slug: "columbarium",
    directory: "columbarium",
    name: "Granite Columbaria",
    eyebrow: "Cremation memorials",
    description:
      "Custom granite columbarium options for single, companion and community niches in cemetery, church and civic memorial projects."
  },
  {
    slug: "designs",
    directory: "Designs",
    name: "Custom Granite Designs",
    eyebrow: "Made to specification",
    description:
      "Distinctive granite artwork and custom design references for monument dealers and memorial professionals."
  }
] as const;

const collectionGuides: Record<
  (typeof collectionDefinitions)[number]["slug"],
  CatalogCollection["guide"]
> = {
  monuments: {
    title: "Specify wholesale granite monuments with fewer surprises.",
    introduction:
      "A gallery image identifies the general design, but a production-ready monument order also connects the die, base, granite color, dimensions, finishes and artwork. Use these designs as a starting point, then confirm the complete specification with our team.",
    items: [
      {
        title: "Choose the monument form",
        description:
          "Compare upright monuments, slants, bevels, markers and custom contours based on the cemetery requirement and the inscription area the family needs."
      },
      {
        title: "Confirm every component",
        description:
          "List die and base dimensions separately, including thickness, finish schedule, margins, vases or accessories and any required foundation constraints."
      },
      {
        title: "Match stock or production",
        description:
          "Check current U.S. inventory for a close match or request custom production when the design, color, size or finish is not available in stock."
      }
    ]
  },
  "mbna-2025": {
    title: "Use the MBNA collection as an evolving dealer reference.",
    introduction:
      "The MBNA 2025 gallery brings together design references gathered around the convention. We will continue adding useful designs as they are reviewed; each reference can be checked against current inventory or developed as a custom order.",
    items: [
      {
        title: "Reference the design shown",
        description:
          "Include the displayed design number or image when requesting pricing so our team can identify the intended shape and carving direction."
      },
      {
        title: "Select the stone specification",
        description:
          "The same design may be quoted in different granite colors, dimensions and finish combinations when material and production requirements permit."
      },
      {
        title: "Verify current availability",
        description:
          "Convention gallery placement does not guarantee stock. We will confirm whether a related piece is available in Elberton or Barre or requires production."
      }
    ]
  },
  benches: {
    title: "Plan a granite memorial bench as a complete assembly.",
    introduction:
      "A memorial bench combines visible design choices with structural and site requirements. Seat length and thickness, support placement, granite color, finish, inscription area and cemetery rules should be confirmed together.",
    items: [
      {
        title: "Seat and support dimensions",
        description:
          "Specify the seat length, width and thickness along with the number, style and spacing of legs, pedestals or other supports."
      },
      {
        title: "Finish and inscription areas",
        description:
          "Identify polished, steeled and rock-pitched surfaces and confirm where lettering, portraits, emblems or decorative carving will be placed."
      },
      {
        title: "Setting requirements",
        description:
          "Confirm cemetery rules, foundation dimensions, anchoring expectations and site access before approving a bench for production or shipment."
      }
    ]
  },
  columbarium: {
    title: "Start every granite columbarium with the niche program.",
    introduction:
      "Columbarium work is project-specific. Capacity, niche dimensions, door layout, inscriptions, structural support, setting conditions and local requirements need to be resolved before a production drawing can be approved.",
    items: [
      {
        title: "Define capacity and niche size",
        description:
          "Confirm the number of single or companion niches and the clear interior dimensions required for the urns the project is intended to serve."
      },
      {
        title: "Coordinate doors and inscriptions",
        description:
          "Review door material, attachment method, numbering, name layout, emblems and replacement access as part of the complete elevation."
      },
      {
        title: "Plan structure and installation",
        description:
          "Foundation, lifting access, component weights, anchoring, drainage and applicable cemetery or municipal requirements must be coordinated for the site."
      }
    ]
  },
  designs: {
    title: "Turn a custom monument reference into an approved specification.",
    introduction:
      "A photograph or sketch communicates intent, but it is not a fabrication drawing. Custom monument work moves forward by resolving geometry, dimensions, stone, finishes, carving, lettering and assembly details.",
    items: [
      {
        title: "Share the clearest source",
        description:
          "Provide the original drawing, vector artwork, high-resolution photograph or design number and identify which features must remain unchanged."
      },
      {
        title: "Resolve fabrication details",
        description:
          "Confirm component dimensions, minimum stone sections, polished and textured surfaces, carving depth and how separate pieces meet or attach."
      },
      {
        title: "Approve before production",
        description:
          "Review the final drawing, spelling, artwork placement, granite color and finish notes before manufacturing begins."
      }
    ]
  }
};

export const collections: CatalogCollection[] = collectionDefinitions.map(
  (collection) => ({
    ...collection,
    images: selectPreferredImages(collection.directory),
    guide: collectionGuides[collection.slug]
  })
);

const featuredHrefById: Record<string, string> = {
  MBNA_2025: "/mbna-2025/",
  Monuments: "/monuments/",
  columbarium: "/columbarium/",
  Designs: "/designs/",
  Benches: "/benches/"
};

const homepageFeaturedOrder = [
  "Benches",
  "Monuments",
  "columbarium",
  "Designs",
  "MBNA_2025"
];

export const featuredCollections: FeaturedCollection[] =
  readJson<FeaturedSource[]>("mobile_app/assets/featured_products.json")
    .map((featured) => {
      const imagePath = publicPathFromAbsoluteUrl(featured.image);
      if (!fs.existsSync(diskPathFromPublicPath(imagePath))) {
        throw new Error(
          `Featured collection image is missing: ${featured.name} -> ${imagePath}`
        );
      }
      return {
        ...featured,
        href: featuredHrefById[featured.id] ?? "/inventory/",
        imagePath
      };
    })
    .sort((left, right) => {
      const leftIndex = homepageFeaturedOrder.indexOf(left.id);
      const rightIndex = homepageFeaturedOrder.indexOf(right.id);
      return (
        (leftIndex === -1 ? Number.MAX_SAFE_INTEGER : leftIndex) -
        (rightIndex === -1 ? Number.MAX_SAFE_INTEGER : rightIndex)
      );
    });

function colorFamily(name: string): GraniteColorFamily {
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

const colorNameOverrides: Record<string, string> = {
  "blue-silk": "Silk Blue",
  "bluepearl": "Blue Pearl",
  "canada-black": "Canada Black",
  "costa-emeralda": "Costa Emeralda",
  "redwood-brown-cats-eye": "Redwood Brown (Cat's Eye)",
  "redwood-red-cats-eye": "Redwood Red (Cat's Eye)",
  "visount-white": "Visount White"
};

const colorAliases: Record<string, string[]> = {
  "blue-silk": ["Blue Silk Granite"]
};

function normalizedColorStem(filename: string): string {
  const normalized = slugify(filename.replace(/\.[^.]+$/, ""));
  if (normalized === "bluepearl") return "blue-pearl";
  if (normalized === "pacific-grey") return "pacific-gray";
  return normalized;
}

function colorMaterial(name: string): GraniteColorMaterial {
  const normalized = name.toLowerCase();
  if (normalized.includes("quartzite")) return "Quartzite";
  if (normalized.includes("marble")) return "Marble";
  if (normalized.includes("sandstone")) return "Sandstone";
  return "Granite";
}

function colorDisplayName(stem: string): {
  name: string;
  material: GraniteColorMaterial;
} {
  const baseName =
    colorNameOverrides[stem] ?? titleCase(stem.replace(/-/g, " "));
  const material = colorMaterial(baseName);
  return {
    name: baseName.toLowerCase().endsWith(material.toLowerCase())
      ? baseName
      : `${baseName} ${material}`,
    material
  };
}

function colorDescription(
  stem: string,
  name: string,
  material: GraniteColorMaterial
): string {
  if (stem === "blue-silk") {
    return "Silk Blue Granite, also known as Blue Silk Granite, is shown as a polished blue-gray natural-stone reference for wholesale monuments, headstones, tablets and bases. Compare its flowing light movement, planned finish, component matching and current U.S. inventory before ordering.";
  }
  return `${name} is shown as a polished natural ${material.toLowerCase()} color reference for wholesale monuments, headstones and memorial components. Compare natural variation, finish, component matching and current inventory with Angel Granites before ordering.`;
}

function selectColorImages(): Array<{ stem: string; image: string }> {
  const absoluteDirectory = path.join(repositoryRoot, "images/colors");
  const selected = new Map<string, string>();
  for (const file of fs
    .readdirSync(absoluteDirectory)
    .filter((name) => imageExtensions.has(path.extname(name).toLowerCase()))
    .filter((name) => fs.statSync(path.join(absoluteDirectory, name)).size > 0)
    .sort((left, right) =>
      left.localeCompare(right, undefined, { numeric: true })
    )) {
    const stem = normalizedColorStem(file);
    const current = selected.get(stem);
    if (!current || path.extname(file).toLowerCase() === ".webp") {
      selected.set(stem, file);
    }
  }
  return [...selected.entries()].map(([stem, file]) => ({
    stem,
    image: `/images/colors/${file}`
  }));
}

function buildColor(
  stem: string,
  image: string
): GraniteColor {
  const { name, material } = colorDisplayName(stem);
  const family = colorFamily(name);
  const slug = material === "Granite" ? `${stem}-granite` : stem;
  return {
    slug,
    name,
    description: colorDescription(stem, name, material),
    image,
    sku: `GC-${stem.toUpperCase()}`,
    material,
    family,
    searchTerms: [
      name,
      ...(colorAliases[stem] ?? []),
      `${name} monument color`,
      `${name} headstone`,
      `${family.toLowerCase()} monument stone`,
      `${family.toLowerCase()} memorial ${material.toLowerCase()}`
    ]
  };
}

const selectedColorImages = selectColorImages();

export const graniteColors: GraniteColor[] = selectedColorImages
  .map(({ stem, image }) => buildColor(stem, image))
  .sort((left, right) =>
    left.name.localeCompare(right.name, undefined, { numeric: true })
  );

export function getCollection(slug: string): CatalogCollection | undefined {
  return collections.find((collection) => collection.slug === slug);
}

export function getColor(slug: string): GraniteColor | undefined {
  return graniteColors.find((color) => color.slug === slug);
}
