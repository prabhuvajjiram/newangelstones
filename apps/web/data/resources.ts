export type ResourceItem = {
  title: string;
  description: string;
};

export type ResourceSection = {
  heading: string;
  introduction?: string;
  items: ResourceItem[];
};

export type ResourceArticle = {
  slug: string;
  category: string;
  title: string;
  shortTitle: string;
  description: string;
  introduction: string;
  sections: ResourceSection[];
};

export const resourceArticles: ResourceArticle[] = [
  {
    slug: "monument-glossary",
    category: "Monuments 101",
    title: "Granite Monument Glossary",
    shortTitle: "Monument glossary",
    description:
      "Plain-language definitions for common granite monument, memorial fabrication, carving, finish and installation terms used by monument dealers.",
    introduction:
      "Use this glossary when reviewing a drawing, inventory description or quote. Terminology can vary by supplier and region, so the approved drawing and written specifications remain the authority for each order.",
    sections: [
      {
        heading: "Monument parts and forms",
        items: [
          { title: "Base", description: "The lower stone component that supports the main monument and provides a stable footprint." },
          { title: "Bevel marker", description: "A low rectangular marker with a slightly sloped top face." },
          { title: "Columbarium", description: "A memorial structure containing niches designed to hold cremation urns." },
          { title: "Die or tablet", description: "The principal upright body of a monument, usually set on a granite base." },
          { title: "Grass marker", description: "A low memorial installed flush with or close to the surrounding grade." },
          { title: "Ledger", description: "A memorial slab positioned horizontally over most or all of a grave space." },
          { title: "Slant", description: "A monument with an inclined inscription face, commonly installed with or without a separate base." },
          { title: "Upright monument", description: "A vertical die or tablet mounted on a base above ground level." }
        ]
      },
      {
        heading: "Fabrication and surface terms",
        items: [
          { title: "Chamfer", description: "A narrow angled cut that removes a sharp square edge." },
          { title: "Etching", description: "Artwork or portrait detail applied to a polished granite surface by laser or hand techniques." },
          { title: "Frosted panel", description: "A matte, lightly blasted area used to contrast with polished granite and improve lettering visibility." },
          { title: "Joint", description: "A prepared surface where one monument component meets another." },
          { title: "Margin", description: "A contrasting finished band around the edge of a panel or stone surface." },
          { title: "Polished", description: "A smooth, reflective granite surface produced through progressively finer abrasives and buffing." },
          { title: "Rock pitch", description: "A deliberately rough edge or face shaped by controlled hand pitching." },
          { title: "Sandblast carving", description: "Lettering or ornament cut into stone by directing abrasive through a prepared stencil." },
          { title: "Steeled or dusted", description: "A smooth matte surface created by blasting rather than polishing." }
        ]
      },
      {
        heading: "Lettering, artwork and setting",
        items: [
          { title: "Epitaph", description: "A memorial phrase or inscription honoring the person being remembered." },
          { title: "Lithichrome", description: "A color treatment used to increase contrast in lettering, panels or carved details." },
          { title: "Raised lettering", description: "Letters left above a recessed surrounding field during carving." },
          { title: "Stencil", description: "A resistant pattern adhered to stone to control where abrasive removes material." },
          { title: "V-sunk lettering", description: "Letters cut with angled sides that meet in a V-shaped recess." },
          { title: "Setting compound", description: "Material placed between monument joints to help seal and cushion assembled stone components." },
          { title: "Wash", description: "A sloped exposed base surface designed to direct water away from the monument." }
        ]
      }
    ]
  },
  {
    slug: "common-monument-shapes",
    category: "Monuments 101",
    title: "Common Granite Monument Shapes",
    shortTitle: "Common monument shapes",
    description:
      "An original dealer reference to common granite monument top profiles, die shapes and practical specification considerations.",
    introduction:
      "The top profile changes the character of an upright monument and can affect fabrication time, usable inscription area and price. Confirm the final outline on an approved drawing because names for similar shapes may differ among manufacturers.",
    sections: [
      {
        heading: "Frequently specified top profiles",
        items: [
          { title: "Serpentine top", description: "A flowing asymmetrical curve that rises and falls across the die. It is one of the most familiar traditional monument profiles." },
          { title: "Flat top", description: "A straight horizontal top that creates a simple, formal outline and preserves a broad rectangular inscription field." },
          { title: "Oval top", description: "A smooth centered arch formed as part of a circle, often used for traditional single and companion monuments." },
          { title: "Gable top", description: "Two sloping planes meet at a centered peak, producing a roof-like silhouette." },
          { title: "Gothic top", description: "Curves rise toward a sharper centered point for a taller, architectural appearance." },
          { title: "Offset roof top", description: "An angular roof profile with the high point moved away from the center, creating a more directional design." },
          { title: "Half-serpentine top", description: "A simplified curved profile that usually rises on one side and transitions toward a lower opposite shoulder." },
          { title: "Custom contour", description: "A shape developed from an approved drawing, including hearts, books, crosses and other memorial-specific outlines." }
        ]
      },
      {
        heading: "What dealers should confirm",
        introduction:
          "Before pricing or production, connect the shape to the full monument specification.",
        items: [
          { title: "Overall dimensions", description: "Specify die width, height and thickness as well as base dimensions; a reference image alone is not a fabrication specification." },
          { title: "Inscription area", description: "Curves and shoulders reduce usable panel space, especially when portraits, emblems or long family names are included." },
          { title: "Finish schedule", description: "Identify which faces, top surfaces and ends are polished, steeled, sawn or rock pitched." },
          { title: "Cemetery requirements", description: "Verify permitted height, width, base projection, foundation and section restrictions before final approval." }
        ]
      }
    ]
  },
  {
    slug: "granite-monument-finishes",
    category: "Materials and fabrication",
    title: "Basic Granite Monument Finishes",
    shortTitle: "Monument finishes",
    description:
      "A practical guide to polished, honed, steeled, dusted, sawn and rock-pitched granite monument surfaces and finish specifications.",
    introduction:
      "A finish describes how a granite surface is processed and how it will look and feel. One monument can combine several finishes to create contrast, manage cost and support lettering or artwork.",
    sections: [
      {
        heading: "Common surface finishes",
        items: [
          { title: "Polished", description: "A smooth reflective surface that deepens the stone color and reveals grain. Polished faces are common for inscriptions and etched artwork." },
          { title: "Honed", description: "A smooth, non-reflective surface produced by stopping before the final polishing stages. It offers subdued contrast against fully polished areas." },
          { title: "Steeled", description: "A uniform matte surface produced by blasting with steel shot. The lighter appearance can contrast strongly with polished granite." },
          { title: "Dusted", description: "A fine matte texture created with abrasive blasting. It is frequently used for inscription panels and decorative contrast." },
          { title: "Sawn", description: "A cut surface left with the relatively smooth texture and possible tooling lines produced by the saw." },
          { title: "Rock pitched", description: "A rough natural-looking surface shaped to a controlled line, commonly used on monument ends, tops and base edges." },
          { title: "Stippled", description: "A textured field made with repeated small tool marks, creating a visibly dimpled surface." },
          { title: "Polished margin", description: "A polished border surrounding a contrasting panel or field, often used to frame lettering." }
        ]
      },
      {
        heading: "Reading a finish specification",
        introduction:
          "Finish abbreviations are compact shop language, but they are not perfectly universal. The drawing should show the exact surfaces included.",
        items: [
          { title: "P1, P2 and higher counts", description: "These commonly indicate how many surfaces are polished. Always identify the actual faces rather than relying on the number alone." },
          { title: "AP or all polished", description: "Typically means all exposed monument surfaces receive a polished finish; concealed joint surfaces are handled according to the fabrication drawing." },
          { title: "BRP or rock-pitched balance", description: "Often indicates that surfaces not specifically polished remain rock pitched. Confirm top and end treatment." },
          { title: "PFT", description: "Commonly refers to a polished flat top, particularly on bases or markers, with the remaining exposed sides separately specified." }
        ]
      },
      {
        heading: "Before approving the order",
        items: [
          { title: "Match every surface", description: "Confirm front, back, top, ends, sides, margins and joint surfaces on the drawing." },
          { title: "Consider artwork contrast", description: "Etching, sandblasted lettering and color treatments perform differently across polished and matte surfaces." },
          { title: "Confirm terminology", description: "If a finish code could be interpreted more than one way, write out the intended treatment before production." }
        ]
      }
    ]
  },
  {
    slug: "how-to-read-monument-dimensions",
    category: "Ordering fundamentals",
    title: "How to Read Granite Monument Dimensions",
    shortTitle: "Reading monument dimensions",
    description:
      "Learn how monument dealers read die, base, marker and bench dimensions, distinguish component sizes and prevent width-height-thickness ordering errors.",
    introduction:
      "Monument dimensions are compact shop language. The same numbers can describe very different pieces if the component, dimension order or units are not stated. Use this guide to prepare a clear inquiry, then confirm the final convention on the approved drawing and order.",
    sections: [
      {
        heading: "Identify the component before reading the numbers",
        introduction:
          "A monument assembly can contain several stones, and each component needs its own dimensions.",
        items: [
          {
            title: "Die or tablet",
            description:
              "The principal upright stone is generally described by width, height and thickness. For example, a dealer may write 3-0 × 2-0 × 0-6 for a die that is three feet wide, two feet high and six inches thick."
          },
          {
            title: "Base",
            description:
              "A base is normally wider and deeper than the die it supports. State its width, depth and height separately rather than combining die and base measurements into one line."
          },
          {
            title: "Markers, slants and bevels",
            description:
              "Low memorials may use length, width and height or a supplier-specific order. A sloped face can also require front and back height information, so the drawing remains authoritative."
          },
          {
            title: "Bench components",
            description:
              "List the seat and every support independently. Include seat length, width and thickness plus leg or pedestal size, quantity and spacing."
          }
        ]
      },
      {
        heading: "Read feet-and-inches notation carefully",
        items: [
          {
            title: "Hyphen notation",
            description:
              "In monument specifications, 3-6 commonly means three feet six inches and 0-8 means eight inches. It does not mean a subtraction calculation or a decimal value."
          },
          {
            title: "Dimension order",
            description:
              "Do not assume every supplier uses the same order. Label width, height, depth or thickness in the inquiry and verify the order shown in the quotation and drawing."
          },
          {
            title: "Overall versus stone size",
            description:
              "Overall installed height can include the foundation, base and die. Keep overall dimensions separate from the fabrication size of each granite component."
          },
          {
            title: "Cemetery limits",
            description:
              "Maximum width, height, base projection and foundation rules may vary by cemetery section. Confirm those limits before finalizing the stone dimensions."
          }
        ]
      },
      {
        heading: "A reliable dimension-check sequence",
        items: [
          {
            title: "Name every piece",
            description:
              "Start with a component list: die, base, marker, vase, bench seat, supports or other stones."
          },
          {
            title: "Label every direction",
            description:
              "Write the dimension names beside the values and keep the same orientation throughout the drawing, quote and order."
          },
          {
            title: "Check design fit",
            description:
              "Make sure the inscription, portrait, emblem and margins fit within the usable polished or carved area, not merely within the overall stone outline."
          },
          {
            title: "Approve the drawing",
            description:
              "Compare the drawing dimensions with the written order and cemetery requirement before authorizing production."
          }
        ]
      }
    ]
  },
  {
    slug: "granite-monument-ordering-checklist",
    category: "Dealer workflow",
    title: "Granite Monument Ordering Checklist",
    shortTitle: "Monument ordering checklist",
    description:
      "A practical wholesale monument ordering checklist covering design, granite color, dimensions, finishes, artwork, cemetery requirements, packing and delivery.",
    introduction:
      "A complete monument inquiry helps the supplier confirm availability and quote the intended work without avoidable revisions. The approved drawing and written order remain the final authority, but this checklist helps dealers gather the right information first.",
    sections: [
      {
        heading: "Design and stone selection",
        items: [
          {
            title: "Design reference",
            description:
              "Provide the Angel Granites design number, a supplier reference, approved sketch or clear photograph. Identify any features that must be changed or preserved."
          },
          {
            title: "Granite color",
            description:
              "Use the recognized stone name and confirm whether natural block variation is acceptable for all pieces in the assembly."
          },
          {
            title: "Stock or custom production",
            description:
              "State whether the request must match current U.S. inventory or can follow a custom-production timeline."
          },
          {
            title: "Quantity",
            description:
              "List the required quantity for each component or complete assembly, especially when ordering duplicate markers, vases or multi-piece projects."
          }
        ]
      },
      {
        heading: "Fabrication specification",
        items: [
          {
            title: "Component dimensions",
            description:
              "List die, base, marker, bench seat, support and accessory sizes independently with labeled width, height, depth or thickness."
          },
          {
            title: "Finish schedule",
            description:
              "Identify the finish on every visible face, top, end, margin and edge. Expand abbreviations when there is any possibility of differing interpretation."
          },
          {
            title: "Lettering and artwork",
            description:
              "Supply final spelling, dates, layout, emblems, portraits and vector artwork where available. State carving, etching, lithichrome and panel requirements."
          },
          {
            title: "Assembly details",
            description:
              "Confirm joints, holes, dowels, vase placement, liners, niche doors and other interfaces between components."
          }
        ]
      },
      {
        heading: "Approval, packing and delivery",
        items: [
          {
            title: "Cemetery approval",
            description:
              "Verify size limits, foundation rules, permitted materials and required dealer or installer documentation before production."
          },
          {
            title: "Drawing approval",
            description:
              "Compare every dimension, inscription and finish note with the written order. Resolve discrepancies before approving."
          },
          {
            title: "Packing requirements",
            description:
              "Identify special crate, pallet, separation, labeling or handling needs and confirm whether related pieces must ship together."
          },
          {
            title: "Delivery information",
            description:
              "Provide the receiving business, complete address, contact, unloading capability, requested timing and any appointment constraints."
          }
        ]
      }
    ]
  }
];

export function getResourceArticle(slug: string) {
  return resourceArticles.find((article) => article.slug === slug);
}
