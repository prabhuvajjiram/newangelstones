# Test info

- Name: Product Search
- Location: /home/user/newangelstones/tests/static.spec.js:98:3

# Error details

```
Error: browserType.launch: 
╔══════════════════════════════════════════════════════╗
║ Host system is missing dependencies to run browsers. ║
║ Missing libraries:                                   ║
║     libglib-2.0.so.0                                 ║
║     libgobject-2.0.so.0                              ║
║     libnss3.so                                       ║
║     libnssutil3.so                                   ║
║     libnspr4.so                                      ║
║     libdbus-1.so.3                                   ║
║     libatk-1.0.so.0                                  ║
║     libatk-bridge-2.0.so.0                           ║
║     libgio-2.0.so.0                                  ║
║     libexpat.so.1                                    ║
║     libatspi.so.0                                    ║
║     libX11.so.6                                      ║
║     libXcomposite.so.1                               ║
║     libXdamage.so.1                                  ║
║     libXext.so.6                                     ║
║     libXfixes.so.3                                   ║
║     libXrandr.so.2                                   ║
║     libgbm.so.1                                      ║
║     libxcb.so.1                                      ║
║     libxkbcommon.so.0                                ║
║     libudev.so.1                                     ║
║     libasound.so.2                                   ║
╚══════════════════════════════════════════════════════╝
```

# Test source

```ts
   1 | const fs = require('fs');
   2 | const cheerio = require('cheerio');
   3 | const { test, expect, chromium } = require('@playwright/test');
   4 |
   5 | test.setTimeout(60000);
   6 | const html = fs.readFileSync('index.html', 'utf-8');
   7 | const $ = cheerio.load(html);
   8 |
   9 | test('Check Home Page Title', () => {
   10 |   const title = $('title').text();
   11 |   expect(title).toContain('Angel Granites');
   12 | });
   13 |
   14 | test('Check for Logo Image', () => {
   15 |   const logo = $('img[src*="logo"]');
   16 |   expect(logo.length).toBeGreaterThan(0);
   17 | });
   18 |
   19 | test('Check Navigation Bar Links', () => {
   20 |   const expectedLinks = ['Home', 'Our Products', 'Featured Products', 'Projects', 'Why Choose Us', 'Contact'];
   21 |   const navLinks = $('#as-nav ul.nav-menu a');
   22 |   const actualLinks = [];
   23 |   navLinks.each((i, el) => {
   24 |     actualLinks.push($(el).text().trim());
   25 |   });
   26 |
   27 |   expect(actualLinks).toEqual(expect.arrayContaining(expectedLinks));
   28 | });
   29 |
   30 | test('Check Footer Text', () => {
   31 |   const footerText = $('#as-footer-main p').text();
   32 |   expect(footerText).toContain('Angel Granites');
   33 | });
   34 |
   35 | test('Check Our Products Section', () => {
   36 |   const ourProductsSection = $('#our-product');
   37 |   expect(ourProductsSection.length).toBeGreaterThan(0);
   38 | });
   39 |
   40 | test('Check Featured Products Section', () => {
   41 |   const featuredProductsSection = $('#featured-products');
   42 |   expect(featuredProductsSection.length).toBeGreaterThan(0);
   43 | });
   44 |
   45 | test('Check Projects Section', () => {
   46 |   const projectsSection = $('#projects');
   47 |   expect(projectsSection.length).toBeGreaterThan(0);
   48 | });
   49 |
   50 | test('Check Why Choose Us Section', () => {
   51 |   const whyChooseUsSection = $('#why-choose-as');
   52 |   expect(whyChooseUsSection.length).toBeGreaterThan(0);
   53 | });
   54 | test('Check Contact Section', () => {
   55 |   const contactSection = $('#get-in-touch');
   56 |   expect(contactSection.length).toBeGreaterThan(0);
   57 | });
   58 |
   59 |
   60 | test('Category Navigation', async ({ page }) => {
   61 |     await page.goto('file://' + process.cwd() + '/index.html');
   62 |     await page.waitForLoadState('networkidle');
   63 |     
   64 |     // Open the "Monuments" category
   65 |     const monumentsLink = await page.waitForSelector('a[href="#monuments-collection"]');
   66 |     await monumentsLink.click();
   67 |   
   68 |     // Wait for the thumbnails to be visible
   69 |     await page.waitForSelector('.category-grid .category-item img');
   70 |   
   71 |     // Verify that all thumbnail images are displayed
   72 |     const thumbnails = await page.$$('.category-grid .category-item img');
   73 |     for (const thumbnail of thumbnails) {
   74 |       const isVisible = await thumbnail.isVisible();
   75 |       expect(isVisible).toBeTruthy();
   76 |     }
   77 |     
   78 |     // Click on a thumbnail image to open the full-size image
   79 |     const firstThumbnail = await page.waitForSelector('.category-grid .category-item img');
   80 |     await firstThumbnail.click();
   81 |   
   82 |     // Wait for the modal to be visible
   83 |     const modalImage = await page.waitForSelector('.modal-image-container .modal-image');
   84 |     await modalImage.isVisible();
   85 |   
   86 |     // Verify that the image is displayed
   87 |     const src = await modalImage.getAttribute('src');
   88 |     expect(src).toBeTruthy();
   89 |   
   90 |     // Close the full-size image
   91 |     const closeButton = await page.waitForSelector('.modal-close');
   92 |     await closeButton.click();
   93 |     
   94 |     // Wait for the modal to be hidden
   95 |     await page.waitForSelector('.modal-image-container .modal-image', { state: 'hidden' });
   96 |   });
   97 |   
>  98 |   test('Product Search', async ({ page }) => {
      |   ^ Error: browserType.launch: 
   99 |     await page.goto('file://' + process.cwd() + '/index.html');
  100 |     await page.waitForLoadState('networkidle');
  101 |     const productCodes = ["AG-116"];
  102 |   
  103 |     for (const productCode of productCodes) {
  104 |       // Search for products using different product codes
  105 |       const searchInput = await page.waitForSelector('#product-search');
  106 |       await searchInput.fill(productCode);
  107 |       await searchInput.press('Enter');
  108 |   
  109 |       // Wait for the image to be displayed
  110 |       const productImages = await page.$$('.category-grid .category-item img');
  111 |       await expect(productImages.length).toBeGreaterThan(0);
  112 |
  113 |       let foundImage = false;
  114 |       for(const productImage of productImages){
  115 |           const imgSrc = await productImage.getAttribute('src');
  116 |           if(imgSrc.includes(productCode)){
  117 |               foundImage = true;
  118 |               await productImage.scrollIntoViewIfNeeded();
  119 |               
  120 |               // Check if the image is visible
  121 |               const isVisible = await productImage.isVisible();
  122 |               expect(isVisible).toBeTruthy();
  123 |               // Click on the product image to open the full-size image
  124 |               await productImage.click();
  125 |             
  126 |               // Wait for the modal to be visible
  127 |               const modalImage = await page.waitForSelector('.modal-image-container .modal-image');
  128 |               await modalImage.isVisible();
  129 |             
  130 |               // Close the full-size image
  131 |               const closeButton = await page.waitForSelector('.modal-close');
  132 |               await closeButton.click();
  133 |             
  134 |               // Wait for the modal to be hidden
  135 |               await page.waitForSelector('.modal-image-container .modal-image', { state: 'hidden' });
  136 |               break;
  137 |           }
  138 |       }
  139 |       expect(foundImage).toBeTruthy();
  140 |     }
  141 |   });
```