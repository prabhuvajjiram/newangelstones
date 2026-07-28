export type CuratedDesign = {
  slug: string;
  code: string;
  name: string;
  productType: "Monument tablet" | "Memorial bench";
  collectionSlug: "monuments" | "mbna-2025" | "benches";
  collectionName: string;
  image: string;
  description: string;
  introduction: string;
  features: Array<{ title: string; description: string }>;
  referenceColors: string[];
  referenceSizes: string[];
  referenceFinish: string;
};

export const curatedDesigns: CuratedDesign[] = [
  {
    slug: "ag-396-carved-angel-heart-monument",
    code: "AG-396",
    name: "Carved Angel Heart Monument",
    productType: "Monument tablet",
    collectionSlug: "mbna-2025",
    collectionName: "MBNA Monument Collection",
    image: "/images/products/MBNA_2025/AG-396.jpg",
    description:
      "AG-396 is a heart-shaped granite monument tablet with a carved angel composition and rose details in multiple reference colors and sizes.",
    introduction:
      "The AG-396 design combines a single-heart outline with a carved angel composition. Current product records describe versions with angel carving on the front and back, split-wing detailing on selected pieces and carved roses on the front. The broad heart form should be reviewed with the planned inscription and portrait area before the final size is approved.",
    features: [
      {
        title: "Single-heart silhouette",
        description:
          "The heart outline establishes the tablet shape and frames the central carving and inscription areas."
      },
      {
        title: "Dimensional angel carving",
        description:
          "Inventory references include carved angel treatments on the front and back, with wing details varying by configuration."
      },
      {
        title: "Rose detailing",
        description:
          "Selected references include carved roses on the front or lower portion of the composition."
      }
    ],
    referenceColors: [
      "Bahama Blue",
      "Blue Pearl",
      "Coral Grey",
      "Gem Mist",
      "India Red",
      "Premium Jet Black"
    ],
    referenceSizes: [
      "2-6 × 0-8 × 2-4",
      "2-8 × 0-8 × 2-8",
      "3-0 × 0-8 × 3-0"
    ],
    referenceFinish: "P5"
  },
  {
    slug: "ag-356-serpentine-fluted-monument",
    code: "AG-356",
    name: "Serpentine Fluted Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-356.jpg",
    description:
      "AG-356 is a serpentine-top granite monument tablet with flutes and band carving, represented in several colors and standard reference sizes.",
    introduction:
      "The AG-356 design uses a traditional serpentine top with vertical flute details and a carved band treatment. Product records show the design in single and companion-width configurations. The curved top and flutes affect the usable inscription field, so lettering, emblems and margins should be laid out on the approved drawing.",
    features: [
      {
        title: "Serpentine top",
        description:
          "A flowing top profile gives the tablet a traditional monument silhouette."
      },
      {
        title: "Vertical flutes",
        description:
          "Fluted side details frame the central polished area and reduce the available lettering width."
      },
      {
        title: "Band carving",
        description:
          "A decorative band treatment appears in multiple inventory descriptions for this design."
      }
    ],
    referenceColors: [
      "Bahama Blue",
      "Imperial Green",
      "India Red",
      "Premium Jet Black"
    ],
    referenceSizes: [
      "2-6 × 0-8 × 2-2",
      "3-0 × 0-8 × 2-2",
      "3-6 × 0-8 × 2-4"
    ],
    referenceFinish: "P5"
  },
  {
    slug: "ag-298-curved-granite-memorial-bench",
    code: "AG-298",
    name: "Curved Granite Memorial Bench",
    productType: "Memorial bench",
    collectionSlug: "benches",
    collectionName: "Granite Memorial Benches",
    image: "/images/products/Benches/AG-298.jpg",
    description:
      "AG-298 is a curved granite memorial bench assembly with coordinated seat and support components in several recorded configurations.",
    introduction:
      "The AG-298 design is a curved granite bench assembled from a shaped seat and coordinated legs or supports. Current product records identify both three-foot and four-foot seat references along with matching support pieces. A complete inquiry should state the seat length, support quantity and spacing, granite color, finish, inscription placement and foundation requirement.",
    features: [
      {
        title: "Curved seat profile",
        description:
          "The shaped seat provides the defining visual line and should be dimensioned independently from the supports."
      },
      {
        title: "Coordinated supports",
        description:
          "Leg and support records use the same AG-298 reference so the complete assembly can be identified."
      },
      {
        title: "Memorial inscription area",
        description:
          "Lettering or artwork placement should be confirmed against the seat curve, finish and usable face."
      }
    ],
    referenceColors: ["Bahama Blue", "Premium Jet Black"],
    referenceSizes: [
      "Seat: 3-0 × 1-4 × 0-4",
      "Seat: 4-0 × 1-4 × 0-4",
      "Support: 0-4 × 1-0 × 1-2"
    ],
    referenceFinish: "P5"
  },
  {
    slug: "ag-233-shamrock-granite-monument",
    code: "AG-233",
    name: "Shamrock Granite Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-233-Sanfransisco Green.jpg",
    description:
      "AG-233 is a shamrock-shaped granite monument tablet recorded in several green granite colors and three reference heights.",
    introduction:
      "The AG-233 tablet uses a distinctive shamrock outline. Inventory references describe the shaped stone without additional carving, allowing the granite movement and monument silhouette to carry the design. The inscription layout should be fitted to the lobed outline rather than treated as a rectangular panel.",
    features: [
      {
        title: "Shamrock outline",
        description:
          "The shaped perimeter creates a recognizable memorial form and a non-rectangular inscription field."
      },
      {
        title: "Stone-forward presentation",
        description:
          "Current records describe the design as stone only, without added carving."
      },
      {
        title: "Green granite references",
        description:
          "Recorded examples emphasize several green materials that complement the botanical form."
      }
    ],
    referenceColors: [
      "Imperial Green",
      "Rain Forest Green",
      "San Francisco Green"
    ],
    referenceSizes: [
      "2-6 × 0-8 × 2-2",
      "2-6 × 0-8 × 2-4",
      "2-6 × 0-8 × 2-6"
    ],
    referenceFinish: "P5"
  },
  {
    slug: "ag-193-single-heart-monument",
    code: "AG-193",
    name: "Single Heart Monument",
    productType: "Monument tablet",
    collectionSlug: "mbna-2025",
    collectionName: "MBNA Monument Collection",
    image: "/images/products/MBNA_2025/AG-193.jpg",
    description:
      "AG-193 is a compact single-heart granite monument tablet represented in Coral Grey, India Red and Premium Jet Black reference records.",
    introduction:
      "The AG-193 design is a compact single-heart tablet suitable for a focused inscription or emblem layout. Product records show multiple widths, heights and thicknesses, so the selected proportions should be checked against the required lettering area and cemetery limits.",
    features: [
      {
        title: "Compact heart form",
        description:
          "The single-heart silhouette creates a clear focal shape in a relatively compact tablet."
      },
      {
        title: "Multiple proportions",
        description:
          "Recorded configurations include more than one width, height and thickness combination."
      },
      {
        title: "Flexible stone selection",
        description:
          "Current references include gray, red and black granite options."
      }
    ],
    referenceColors: ["Coral Grey", "India Red", "Premium Jet Black"],
    referenceSizes: [
      "1-8 × 0-8 × 2-0",
      "2-0 × 0-6 × 2-2",
      "2-0 × 0-8 × 2-4"
    ],
    referenceFinish: "P5"
  },
  {
    slug: "ag-196-uncarved-granite-monument",
    code: "AG-196",
    name: "Uncarved Granite Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-196.jpg",
    description:
      "AG-196 is a shaped, uncarved granite monument tablet that provides an open surface for dealer-planned lettering and artwork.",
    introduction:
      "The AG-196 product records consistently identify a shaped tablet with no carving. That makes the design a useful starting point when the dealer needs to plan lettering, portraits or emblems without competing factory-carved ornament. The final layout still needs to respect the actual contour, margins and finish schedule.",
    features: [
      {
        title: "Shaped tablet",
        description:
          "The monument has a defined outline while retaining a comparatively open central face."
      },
      {
        title: "No factory carving",
        description:
          "Current records describe the reference stone without carved ornament."
      },
      {
        title: "Layout flexibility",
        description:
          "The open face can support dealer-planned lettering and artwork after the usable area is confirmed."
      }
    ],
    referenceColors: ["Bahama Blue", "Premium Jet Black"],
    referenceSizes: ["3-0 × 0-6 × 2-0", "3-6 × 0-8 × 2-4"],
    referenceFinish: "P5"
  },
  {
    slug: "ag-525-double-heart-rose-monument",
    code: "AG-525",
    name: "Double Heart Rose Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-525 Premium Jet Black.jpg",
    description:
      "AG-525 is a double-heart granite monument tablet with rose carving and raised or frosted panel treatments in selected references.",
    introduction:
      "The AG-525 design combines a companion double-heart form with prominent rose carving. Product records include deep-carved rose treatments, a raised front panel on a selected reference and a frosted lower treatment. Because these details affect lettering space and contrast, the final carving and panel notes should be shown explicitly on the drawing.",
    features: [
      {
        title: "Double-heart composition",
        description:
          "The companion form provides two related focal areas within one tablet."
      },
      {
        title: "Deep rose carving",
        description:
          "Multiple product references identify carved or deep-carved rose ornament."
      },
      {
        title: "Panel and frost options",
        description:
          "Selected records include raised-panel and frosted lower-surface treatments."
      }
    ],
    referenceColors: ["India Red", "Premium Jet Black"],
    referenceSizes: [
      "3-0 × 0-8 × 2-2",
      "3-6 × 0-8 × 2-2",
      "3-6 × 0-8 × 2-4"
    ],
    referenceFinish: "P5; selected records include frosted details"
  },
  {
    slug: "ag-570-cross-granite-monument",
    code: "AG-570",
    name: "Cross Granite Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-570.jpg",
    description:
      "AG-570 is a shaped granite monument tablet with a cross design, recorded in Premium Jet Black and Taj Aurora granite.",
    introduction:
      "The AG-570 design is a compact shaped tablet with a cross as the principal visual element. Current records consistently use the same reference size. Lettering and dates should be arranged around the cross with enough margin to preserve the design hierarchy.",
    features: [
      {
        title: "Cross focal point",
        description:
          "The cross is the principal design element and should remain visually clear after lettering is added."
      },
      {
        title: "Compact proportions",
        description:
          "Recorded configurations use a two-foot width with a taller shaped profile."
      },
      {
        title: "Contrasting stone choices",
        description:
          "Current references include both Premium Jet Black and Taj Aurora granite."
      }
    ],
    referenceColors: ["Premium Jet Black", "Taj Aurora"],
    referenceSizes: ["2-0 × 0-8 × 2-4"],
    referenceFinish: "P5"
  },
  {
    slug: "ag-631a-antiqued-heart-monument",
    code: "AG-631A",
    name: "Antiqued Heart Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-631A.jpg",
    description:
      "AG-631A is a single-heart granite monument tablet with antiqued carving or floral treatments in several recorded stone colors.",
    introduction:
      "The AG-631A design uses a single-heart outline with antiqued carving. Inventory descriptions include heart and floral treatments depending on the stone reference. The antique treatment, carving depth and any added color should be stated on the approved drawing rather than assumed from the photograph.",
    features: [
      {
        title: "Single-heart shape",
        description:
          "The compact heart profile defines the tablet and central inscription area."
      },
      {
        title: "Antiqued carving",
        description:
          "Current records identify antiqued carving as a recurring design treatment."
      },
      {
        title: "Consistent reference size",
        description:
          "The recorded inventory configurations share the same principal dimensions."
      }
    ],
    referenceColors: [
      "Bahama Blue",
      "Paradiso",
      "Premium Jet Black",
      "Taj Aurora"
    ],
    referenceSizes: ["2-0 × 0-8 × 2-4"],
    referenceFinish: "P5 with antiqued carving references"
  },
  {
    slug: "ag-837a-carved-floral-monument",
    code: "AG-837A",
    name: "Carved Floral Monument",
    productType: "Monument tablet",
    collectionSlug: "monuments",
    collectionName: "Granite Monuments",
    image: "/images/products/Monuments/AG-837A.jpg",
    description:
      "AG-837A is a shaped granite monument tablet with carved floral ornament, including antiqued treatments in selected records.",
    introduction:
      "The AG-837A design is a compact shaped tablet with carved flowers as the primary ornament. Product records include both carved and antiqued-carved floral treatments. The final order should distinguish the carving treatment and confirm the inscription area remaining around the flowers.",
    features: [
      {
        title: "Floral carving",
        description:
          "Carved flowers provide the identifying decorative treatment for the design."
      },
      {
        title: "Antiqued option",
        description:
          "Selected product records specify an antiqued treatment on the carved flowers."
      },
      {
        title: "Compact reference format",
        description:
          "Recorded configurations share a compact two-foot-wide tablet size."
      }
    ],
    referenceColors: ["Bahama Blue", "Premium Jet Black"],
    referenceSizes: ["2-0 × 0-8 × 2-4"],
    referenceFinish: "P5 with carved or antiqued-carved flowers"
  }
];

export function getCuratedDesign(slug: string) {
  return curatedDesigns.find((design) => design.slug === slug);
}

export const designHrefByImage = new Map(
  curatedDesigns.map((design) => [
    design.image,
    `/designs/${design.slug}/`
  ])
);
