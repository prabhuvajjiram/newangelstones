export const services = [
  {
    title: "Granite monuments and headstones",
    description:
      "Domestic and imported monument granite in more than 100 colors, with standard dimensions in inventory and custom sizes fabricated to specification for dealer and wholesale orders."
  },
  {
    title: "Sandblasting services",
    description:
      "In-house precision sandblasting for names, dates, emblems and decorative patterns, including finishing work on qualifying dealer-supplied blanks."
  },
  {
    title: "Custom etching",
    description:
      "Laser and hand-etching options for portraits, landscapes and detailed memorial artwork, coordinated as part of a finished dealer order."
  },
  {
    title: "Custom design and fabrication",
    description:
      "Custom dimensions, shapes, finishes and artwork built from dealer specifications, with design review before production."
  }
] as const;

export const dealerAdvantages = [
  {
    title: "Direct production support",
    description:
      "Our Elberton production operation and Barre warehouse support direct wholesale relationships without unnecessary layers."
  },
  {
    title: "More than 100 granite colors",
    description:
      "Domestic and imported stone colors can be coordinated through one supplier, from classic grays and blacks to reds, blues and specialty materials."
  },
  {
    title: "Ready-to-ship U.S. inventory",
    description:
      "Search current stock by product type, color, design, finish, size and location before committing to a longer custom-production timeline."
  },
  {
    title: "Nationwide freight coordination",
    description:
      "Orders are protected, palletized and prepared for shipment, with consolidated freight options considered when practical."
  },
  {
    title: "Dealer-focused pricing",
    description:
      "Trade-account, quantity and project requirements are reviewed by our team so dealers can quote from confirmed specifications."
  },
  {
    title: "Finished monument capabilities",
    description:
      "Fabrication, sandblasting, etching and artwork coordination can keep more of the finished memorial workflow with one team."
  }
] as const;

export const locations = [
  {
    slug: "elberton-ga",
    name: "Elberton, Georgia",
    shortName: "Elberton",
    eyebrow: "Production and inventory",
    addressLines: [
      "1187 Old Middleton Rd, Elberton, GA 30635",
      "203 Williams St, Elberton, GA"
    ],
    schemaAddress: {
      streetAddress: "1187 Old Middleton Rd",
      addressLocality: "Elberton",
      addressRegion: "GA",
      postalCode: "30635"
    },
    description:
      "Angel Granites operates in Elberton, the center of the U.S. granite monument industry, supporting monument production, inventory and wholesale dealer orders.",
    regionCopy:
      "Our Elberton team works with monument dealers and memorial professionals on granite selection, standard and custom monument specifications, finishing requirements and shipment planning.",
    serviceArea: "Monument dealers and memorial professionals across the United States",
    capabilities: [
      {
        title: "Production coordination",
        description:
          "Standard and custom granite monument requirements can be reviewed with the production team close to the work."
      },
      {
        title: "U.S. inventory support",
        description:
          "Dealers can search current stock and confirm design, color, size, finish, quantity and warehouse details before ordering."
      },
      {
        title: "Finished monument services",
        description:
          "Sandblasting, etching, artwork and fabrication requirements can be coordinated as part of a complete dealer order."
      }
    ]
  },
  {
    slug: "barre-vt",
    name: "Barre, Vermont",
    shortName: "Barre",
    eyebrow: "Warehouse and dealer support",
    addressLines: ["15 Blackwell St, Barre, VT 05641"],
    schemaAddress: {
      streetAddress: "15 Blackwell St",
      addressLocality: "Barre",
      addressRegion: "VT",
      postalCode: "05641"
    },
    description:
      "The Angel Granites Barre warehouse supports wholesale granite monument availability and dealer service in Vermont and the Northeast.",
    regionCopy:
      "Barre has a long granite-industry history. Our local warehouse extends Angel Granites inventory access and dealer support beyond Elberton while keeping specifications, availability and freight coordination connected.",
    serviceArea: "Vermont, New England and monument dealers throughout the Northeast",
    capabilities: [
      {
        title: "Northeast inventory access",
        description:
          "The Barre warehouse gives regional dealers another location for checking available granite monument stock and related components."
      },
      {
        title: "Connected specifications",
        description:
          "Design, granite color, dimensions, finishes and artwork requirements stay connected with the broader Angel Granites team."
      },
      {
        title: "Freight and pickup planning",
        description:
          "Dealers can coordinate receiving requirements, shipment timing or warehouse pickup details after availability is confirmed."
      }
    ]
  }
] as const;

export type BusinessLocation = (typeof locations)[number];
