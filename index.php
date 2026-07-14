<?php
/**
 * SPA Router – Angel Granites
 *
 * Provides unique SEO <head> for each section URL while serving the
 * identical SPA body from index.html.  Googlebot sees real, meaningful
 * per-page metadata; users get the full SPA experience unchanged.
 *
 * How it works
 * ─────────────
 *  /monuments/      → unique title / description / canonical / JSON-LD
 *                     + same SPA body from index.html
 *                     + tiny inline script that triggers existing
 *                       deep-linking.js via popstate (no JS changes needed)
 *
 *  anything else    → serve index.html as-is (no processing cost)
 */

// ── Section route definitions ────────────────────────────────────────────────
$routes = [

    'monuments' => [
        'title'       => 'Granite Monuments &amp; Headstones | Angel Granites',
        'description' => 'Browse 58+ in-stock granite monuments and headstones ready to ship from Elberton, GA. Custom memorials, wholesale pricing. Request a free quote.',
        'canonical'   => 'https://www.theangelstones.com/monuments/',
        'og_title'    => 'Granite Monuments & Headstones | Angel Granites',
        'og_desc'     => 'Browse 58+ in-stock granite monuments and headstones. Custom memorials from Elberton, GA.',
        'og_image'    => 'https://www.theangelstones.com/images/products/Monuments/granite-monuments-project02.jpg',
        'tw_title'    => 'Granite Monuments & Headstones | Angel Granites',
        'tw_desc'     => 'Browse 58+ in-stock granite monuments and headstones ready to ship from Elberton, GA.',
        'h1'          => 'Granite Monuments & Headstones',
        'category'    => 'monuments',   // maps to ?category= deep-link
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'granite monuments, headstones, memorial stones, cemetery monuments, Elberton GA',
        'content'     => <<<'HTML'
            <section class="section-padding seo-landing-content" aria-labelledby="monuments-page-heading">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="section-header text-center">
                                <div class="subtitle">Wholesale Monument Supply</div>
                                <h2 id="monuments-page-heading">Granite Headstones and Monuments from Elberton, GA</h2>
                            </div>
                            <p>Angel Granites supplies monument dealers, funeral homes, and memorial retailers with in-stock and custom granite headstones, cemetery monuments, and memorial stones. Our Elberton, Georgia inventory includes upright monuments, slants, bevels, markers, bases, benches, and specialty designs in a wide selection of granite colors.</p>
                            <p>Choose a ready-to-ship design or work with our team on dimensions, granite color, finish, carving, sandblast, and etching requirements. We provide dealer pricing, bulk ordering options, and nationwide shipping for qualified wholesale customers.</p>
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <h3>Ready-to-ship inventory</h3>
                                    <p>Browse available granite monuments and headstones already stocked in the United States for faster fulfillment.</p>
                                </div>
                                <div class="col-md-4">
                                    <h3>Custom monument production</h3>
                                    <p>Specify the design, size, granite color, finish, and artwork needed for your customer or cemetery project.</p>
                                </div>
                                <div class="col-md-4">
                                    <h3>Dealer and wholesale support</h3>
                                    <p>Request current availability, volume pricing, lead times, and shipping assistance from our monument supply team.</p>
                                </div>
                            </div>
                            <div class="text-center mt-4">
                                <a class="button" href="#featured-products">Browse Monument Inventory</a>
                                <a class="button" href="#get-in-touch">Request Dealer Pricing</a>
                            </div>
                            <div class="mt-5" aria-labelledby="monument-faq-heading">
                                <h2 id="monument-faq-heading">Granite Monument Questions</h2>
                                <h3>Do you sell directly to monument dealers and funeral homes?</h3>
                                <p>Yes. Angel Granites serves monument dealers, funeral homes, memorial retailers, and other qualified wholesale buyers throughout the United States.</p>
                                <h3>Are granite headstones available for immediate shipment?</h3>
                                <p>Selected monuments and memorial stones are stocked in Elberton, GA and ready to ship. Availability changes, so contact our team to confirm the current design, size, and granite color.</p>
                                <h3>Can you manufacture custom cemetery monuments?</h3>
                                <p>Yes. We can produce custom granite monuments based on the required design, dimensions, color, finish, carving, sandblast, and etching specifications.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
HTML,
        'faqs'        => [
            [
                'question' => 'Do you sell directly to monument dealers and funeral homes?',
                'answer'   => 'Yes. Angel Granites serves monument dealers, funeral homes, memorial retailers, and other qualified wholesale buyers throughout the United States.',
            ],
            [
                'question' => 'Are granite headstones available for immediate shipment?',
                'answer'   => 'Selected monuments and memorial stones are stocked in Elberton, GA and ready to ship. Availability changes, so contact our team to confirm the current design, size, and granite color.',
            ],
            [
                'question' => 'Can you manufacture custom cemetery monuments?',
                'answer'   => 'Yes. We can produce custom granite monuments based on the required design, dimensions, color, finish, carving, sandblast, and etching specifications.',
            ],
        ],
    ],

    'inventory' => [
        'title'       => 'Current Inventory – Monuments, Benches &amp; More | Angel Granites',
        'description' => 'View our full inventory of granite monuments, memorial benches, columbarium niches and custom designs. In-stock items ready to ship from Elberton, GA.',
        'canonical'   => 'https://www.theangelstones.com/inventory/',
        'og_title'    => 'Current Inventory | Angel Granites',
        'og_desc'     => 'Full granite monument inventory — in-stock items ready to ship from Elberton, GA.',
        'og_image'    => 'https://www.theangelstones.com/images/products/Monuments/AG-539.jpg',
        'tw_title'    => 'Current Inventory | Angel Granites',
        'tw_desc'     => 'Full granite monument inventory — in-stock items ready to ship from Elberton, GA.',
        'h1'          => 'Current Inventory',
        'category'    => null,
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'granite inventory, monuments in stock, ready to ship monuments, headstones inventory',
    ],

    'granite-colors' => [
        'title'       => 'Granite Colors – 30+ Monument Color Options | Angel Granites',
        'description' => 'Choose from 30+ premium granite colors: Black Pearl, Blue Pearl, India Red, Galaxy Black, Green Pearl and more. Compare colors for your custom monument or headstone.',
        'canonical'   => 'https://www.theangelstones.com/granite-colors/',
        'og_title'    => 'Granite Colors | Angel Granites',
        'og_desc'     => 'Choose from 30+ premium granite colors for your custom monument or headstone.',
        'og_image'    => 'https://www.theangelstones.com/images/colors/Premium Black.jpg',
        'tw_title'    => 'Granite Colors | Angel Granites',
        'tw_desc'     => 'Choose from 30+ premium granite colors for your custom monument or headstone.',
        'h1'          => 'Granite Colors',
        'category'    => null,
        'section'     => 'variety-of-granites',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'granite colors, Black Pearl granite, Blue Pearl granite, India Red granite, monument colors',
    ],

    'contact' => [
        'title'       => 'Contact Angel Granites | Request a Quote',
        'description' => 'Contact Angel Granites for custom monument quotes, wholesale pricing and memorial stone enquiries. Call +1-706-262-7177 or fill in our online quote form.',
        'canonical'   => 'https://www.theangelstones.com/contact/',
        'og_title'    => 'Contact Angel Granites | Request a Quote',
        'og_desc'     => 'Contact us for custom monument quotes and wholesale pricing. Call +1-706-262-7177.',
        'og_image'    => 'https://www.theangelstones.com/images/ag_logo.svg',
        'tw_title'    => 'Contact Angel Granites | Request a Quote',
        'tw_desc'     => 'Custom monument quotes and wholesale pricing. Call +1-706-262-7177.',
        'h1'          => 'Contact Angel Granites',
        'category'    => null,
        'section'     => 'get-in-touch',
        'schema_type' => 'ContactPage',
        'keywords'    => 'contact Angel Granites, monument quote, granite headstone quote, Elberton GA',
    ],

    'benches' => [
        'title'       => 'Granite Memorial Benches | Angel Granites',
        'description' => 'Custom granite memorial benches for cemeteries, tribute gardens and outdoor memorials. Durable, polished and personalised. Request a quote from Angel Granites.',
        'canonical'   => 'https://www.theangelstones.com/benches/',
        'og_title'    => 'Granite Memorial Benches | Angel Granites',
        'og_desc'     => 'Custom granite memorial benches for cemeteries and tribute gardens. Request a quote.',
        'og_image'    => 'https://www.theangelstones.com/images/products/Benches/Fountain2.jpg',
        'tw_title'    => 'Granite Memorial Benches | Angel Granites',
        'tw_desc'     => 'Custom granite memorial benches for cemeteries and tribute gardens.',
        'h1'          => 'Granite Memorial Benches',
        'category'    => 'benches',
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'granite memorial benches, cemetery benches, memorial garden benches, granite benches',
    ],

    'designs' => [
        'title'       => 'Custom Granite Designs &amp; Etchings | Angel Granites',
        'description' => 'Unique custom granite designs including portrait etchings, laser artwork and decorative stonework. Collaborate with our designers for a one-of-a-kind memorial.',
        'canonical'   => 'https://www.theangelstones.com/designs/',
        'og_title'    => 'Custom Granite Designs & Etchings | Angel Granites',
        'og_desc'     => 'Unique custom granite designs: portrait etchings, laser artwork and decorative stonework.',
        'og_image'    => 'https://www.theangelstones.com/images/products/Designs/chess.jpg',
        'tw_title'    => 'Custom Granite Designs & Etchings | Angel Granites',
        'tw_desc'     => 'Custom granite designs: portrait etchings, laser artwork and memorial stonework.',
        'h1'          => 'Custom Granite Designs',
        'category'    => 'designs',
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'custom granite designs, granite etchings, laser etched granite, portrait granite',
    ],

    'columbarium' => [
        'title'       => 'Granite Columbarium Niches &amp; Vaults | Angel Granites',
        'description' => 'Custom granite columbarium niches and community mausoleums. Factory-direct pricing from Elberton, GA. Request a quote for single, double or family columbarium units.',
        'canonical'   => 'https://www.theangelstones.com/columbarium/',
        'og_title'    => 'Granite Columbarium Niches & Vaults | Angel Granites',
        'og_desc'     => 'Custom granite columbarium niches — single, double and family units. Factory-direct from Elberton, GA.',
        'og_image'    => 'https://www.theangelstones.com/images/products/columbarium/customized-designs-project03.jpg',
        'tw_title'    => 'Granite Columbarium Niches & Vaults | Angel Granites',
        'tw_desc'     => 'Custom granite columbarium niches and mausoleums. Factory-direct pricing.',
        'h1'          => 'Granite Columbarium Niches',
        'category'    => 'columbarium',
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'granite columbarium, columbarium niches, granite mausoleum, cremation vaults, Elberton GA',
    ],

    'mbna-2025' => [
        'title'       => 'MBNA 2025 Granite Monument Collection | Angel Granites',
        'description' => 'Browse the exclusive MBNA 2025 ready-to-ship granite monument collection. Premium in-stock designs at factory-direct wholesale pricing from Elberton, GA.',
        'canonical'   => 'https://www.theangelstones.com/mbna-2025/',
        'og_title'    => 'MBNA 2025 Monument Collection | Angel Granites',
        'og_desc'     => 'Exclusive MBNA 2025 in-stock granite monuments at wholesale pricing. Ready to ship from Elberton, GA.',
        'og_image'    => 'https://www.theangelstones.com/images/products/MBNA_2025/AG-116.jpg',
        'tw_title'    => 'MBNA 2025 Monument Collection | Angel Granites',
        'tw_desc'     => 'Exclusive MBNA 2025 granite monument collection. In-stock, factory-direct pricing.',
        'h1'          => 'MBNA 2025 Monument Collection',
        'category'    => 'mbna_2025',
        'section'     => 'featured-products',
        'schema_type' => 'CollectionPage',
        'keywords'    => 'MBNA 2025 monuments, granite monuments wholesale, in-stock monuments, Angel Granites MBNA',
    ],

];

// ── Route resolution ─────────────────────────────────────────────────────────
$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if (!isset($routes[$path])) {
    // No match — serve index.html unchanged (zero performance cost for main traffic)
    readfile(__DIR__ . '/index.html');
    exit;
}

$page = $routes[$path];

// ── Load and patch index.html ────────────────────────────────────────────────
$html = file_get_contents(__DIR__ . '/index.html');

// 0. Inject <base href="/"> so all relative asset paths (css/, js/, images/)
//    resolve from root rather than from /monuments/ etc.
$html = preg_replace(
    '/<head(\s|>)/i',
    '<head$1<base href="/">',
    $html, 1
);

// 1. <title>
$html = preg_replace(
    '/<title>[^<]+<\/title>/',
    '<title>' . $page['title'] . '</title>',
    $html, 1
);

// Give each routed page a visible, route-specific H1 instead of leaving the
// homepage heading in place. This keeps the rendered content aligned with the
// title, canonical URL, and structured data.
$html = preg_replace(
    '/<h1\s+id="main-heading">.*?<\/h1>/is',
    '<h1 id="main-heading">' . $page['h1'] . '</h1>',
    $html, 1
);

// 2. meta description
$html = preg_replace(
    '/<meta\s+name=["\']description["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta name="description" content="' . htmlspecialchars($page['description'], ENT_QUOTES | ENT_HTML5) . '">',
    $html, 1
);

// 3. canonical + 3b. inject meta keywords immediately after
$canonicalTag = '<link rel="canonical" href="' . $page['canonical'] . '" />';
$keywordsTag  = '<meta name="keywords" content="' . htmlspecialchars($page['keywords'], ENT_QUOTES | ENT_HTML5) . '">';
$html = preg_replace(
    '/<link\s+rel=["\']canonical["\']\s+href=["\'][^"\']*["\']\s*\/?>/i',
    $canonicalTag . "\n    " . $keywordsTag,
    $html, 1
);

// 4. og:title
$html = preg_replace(
    '/<meta\s+property=["\']og:title["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta property="og:title" content="' . htmlspecialchars($page['og_title'], ENT_QUOTES | ENT_HTML5) . '">',
    $html, 1
);

// 5. og:description
$html = preg_replace(
    '/<meta\s+property=["\']og:description["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta property="og:description" content="' . htmlspecialchars($page['og_desc'], ENT_QUOTES | ENT_HTML5) . '">',
    $html, 1
);

// 6. og:url
$html = preg_replace(
    '/<meta\s+property=["\']og:url["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta property="og:url" content="' . $page['canonical'] . '">',
    $html, 1
);

// 7. og:image
$html = preg_replace(
    '/<meta\s+property=["\']og:image["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta property="og:image" content="' . $page['og_image'] . '">',
    $html, 1
);

// 8. twitter:title
$html = preg_replace(
    '/<meta\s+name=["\']twitter:title["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta name="twitter:title" content="' . htmlspecialchars($page['tw_title'], ENT_QUOTES | ENT_HTML5) . '">',
    $html, 1
);

// 9. twitter:description
$html = preg_replace(
    '/<meta\s+name=["\']twitter:description["\']\s+content=["\'][^"\']*["\']\s*\/?>/i',
    '<meta name="twitter:description" content="' . htmlspecialchars($page['tw_desc'], ENT_QUOTES | ENT_HTML5) . '">',
    $html, 1
);

// 10. Inject page-specific JSON-LD schema + BreadcrumbList before </head>
$schemaData = [
    '@context' => 'https://schema.org',
    '@type'    => $page['schema_type'],
    'name'     => html_entity_decode(strip_tags($page['title'])),
    'url'      => $page['canonical'],
    'description' => $page['description'],
    'isPartOf' => [
        '@type' => 'WebSite',
        'url'   => 'https://www.theangelstones.com/',
        'name'  => 'Angel Granites',
    ],
    'breadcrumb' => [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type'    => 'ListItem',
                'position' => 1,
                'name'     => 'Home',
                'item'     => 'https://www.theangelstones.com/',
            ],
            [
                '@type'    => 'ListItem',
                'position' => 2,
                'name'     => $page['h1'],
                'item'     => $page['canonical'],
            ],
        ],
    ],
];

$schemaTag = '<script type="application/ld+json" id="page-schema">'
    . json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    . '</script>';

if (!empty($page['faqs'])) {
    $faqSchema = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(static function (array $faq): array {
            return [
                '@type'          => 'Question',
                'name'           => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $faq['answer'],
                ],
            ];
        }, $page['faqs']),
    ];
    $schemaTag .= "\n" . '<script type="application/ld+json" id="faq-schema">'
        . json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>';
}

$html = str_replace('</head>', $schemaTag . "\n</head>", $html);

// Add substantive, visible route content before the shared catalog. Metadata
// alone is not enough to make a section URL a useful landing page.
if (!empty($page['content'])) {
    $html = str_replace(
        '<!--Featured Products Section Start Here-->',
        $page['content'] . "\n            <!--Featured Products Section Start Here-->",
        $html
    );
}

// 11. Inject auto-navigation script before </body>
//
//     Strategy: fire on window 'load' (after deep-linking.js is bootstrapped)
//     a) If page has a ?category=… mapping — push it into the URL and fire
//        a synthetic popstate so the existing handleCategoryDeepLink() picks it up.
//     b) Otherwise — just smooth-scroll to the target section by ID.
//
//     We NEVER navigate away; the browser URL stays at /monuments/ etc.
$safeSection  = json_encode($page['section']);
$safeCategory = json_encode($page['category']);

$autoNav = <<<SCRIPT

    <!-- SPA section router — injected by index.php -->
    <script id="spa-section-router">(function(){
        var section  = {$safeSection};
        var category = {$safeCategory};

        window.addEventListener('load', function() {
            var sp = new URLSearchParams(window.location.search);

            if (category && !sp.has('category')) {
                // Add ?category= and trigger deep-linking.js popstate handler
                sp.set('category', category);
                var newUrl = window.location.pathname + '?' + sp.toString();
                window.history.replaceState(null, '', newUrl);
                window.dispatchEvent(new PopStateEvent('popstate', { state: { category: category } }));
            } else if (section) {
                // No category — scroll directly to the section
                var el = document.getElementById(section);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, { once: true });
    })();</script>
SCRIPT;

$html = str_replace('</body>', $autoNav . "\n</body>", $html);

// 12. Tag <html> with data-seo-page for analytics / debugging (optional)
$html = preg_replace(
    '/<html(\s)/',
    '<html data-seo-page="' . htmlspecialchars($path, ENT_QUOTES | ENT_HTML5) . '"$1',
    $html, 1
);

// ── Serve ─────────────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
// Allow CDN / proxy caching for 1 hour; revalidate after
header('Cache-Control: public, max-age=3600, stale-while-revalidate=86400');
echo $html;
