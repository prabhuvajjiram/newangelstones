const fs = require('fs');
const cheerio = require('cheerio');
const { test, expect, chromium } = require('@playwright/test');

test.setTimeout(60000);
const html = fs.readFileSync('index.html', 'utf-8');
const $ = cheerio.load(html);

test('Check Home Page Title', () => {
  const title = $('title').text();
  expect(title).toContain('Angel Granites');
});

test('Check for Logo Image', () => {
  const logo = $('img[src*="logo"]');
  expect(logo.length).toBeGreaterThan(0);
});

test('Check Navigation Bar Links', () => {
  const expectedLinks = ['Home', 'Our Products', 'Featured Products', 'Projects', 'Why Choose Us', 'Contact'];
  const navLinks = $('#as-nav ul.nav-menu a');
  const actualLinks = [];
  navLinks.each((i, el) => {
    actualLinks.push($(el).text().trim());
  });

  expect(actualLinks).toEqual(expect.arrayContaining(expectedLinks));
});

test('Check Footer Text', () => {
  const footerText = $('#as-footer-main p').text();
  expect(footerText).toContain('Angel Granites');
});

test('Check Our Products Section', () => {
  const ourProductsSection = $('#our-product');
  expect(ourProductsSection.length).toBeGreaterThan(0);
});

test('Check Featured Products Section', () => {
  const featuredProductsSection = $('#featured-products');
  expect(featuredProductsSection.length).toBeGreaterThan(0);
});

test('Check Projects Section', () => {
  const projectsSection = $('#projects');
  expect(projectsSection.length).toBeGreaterThan(0);
});

test('Check Why Choose Us Section', () => {
  const whyChooseUsSection = $('#why-choose-as');
  expect(whyChooseUsSection.length).toBeGreaterThan(0);
});
test('Check Contact Section', () => {
  const contactSection = $('#get-in-touch');
  expect(contactSection.length).toBeGreaterThan(0);
});


test('Category Navigation', async ({ page }) => {
    await page.goto('file://' + process.cwd() + '/index.html');
    await page.waitForLoadState('networkidle');
    
    // Open the "Monuments" category
    const monumentsLink = await page.waitForSelector('a[href="#monuments-collection"]');
    await monumentsLink.click();
  
    // Wait for the thumbnails to be visible
    await page.waitForSelector('.category-grid .category-item img');
  
    // Verify that all thumbnail images are displayed
    const thumbnails = await page.$$('.category-grid .category-item img');
    for (const thumbnail of thumbnails) {
      const isVisible = await thumbnail.isVisible();
      expect(isVisible).toBeTruthy();
    }
    
    // Click on a thumbnail image to open the full-size image
    const firstThumbnail = await page.waitForSelector('.category-grid .category-item img');
    await firstThumbnail.click();
  
    // Wait for the modal to be visible
    const modalImage = await page.waitForSelector('.modal-image-container .modal-image');
    await modalImage.isVisible();
  
    // Verify that the image is displayed
    const src = await modalImage.getAttribute('src');
    expect(src).toBeTruthy();
  
    // Close the full-size image
    const closeButton = await page.waitForSelector('.modal-close');
    await closeButton.click();
    
    // Wait for the modal to be hidden
    await page.waitForSelector('.modal-image-container .modal-image', { state: 'hidden' });
  });
  
  test('Product Search', async ({ page }) => {
    await page.goto('file://' + process.cwd() + '/index.html');
    await page.waitForLoadState('networkidle');
    const productCodes = ["AG-116"];
  
    for (const productCode of productCodes) {
      // Search for products using different product codes
      const searchInput = await page.waitForSelector('#product-search');
      await searchInput.fill(productCode);
      await searchInput.press('Enter');
  
      // Wait for the image to be displayed
      const productImages = await page.$$('.category-grid .category-item img');
      await expect(productImages.length).toBeGreaterThan(0);

      let foundImage = false;
      for(const productImage of productImages){
          const imgSrc = await productImage.getAttribute('src');
          if(imgSrc.includes(productCode)){
              foundImage = true;
              await productImage.scrollIntoViewIfNeeded();
              
              // Check if the image is visible
              const isVisible = await productImage.isVisible();
              expect(isVisible).toBeTruthy();
              // Click on the product image to open the full-size image
              await productImage.click();
            
              // Wait for the modal to be visible
              const modalImage = await page.waitForSelector('.modal-image-container .modal-image');
              await modalImage.isVisible();
            
              // Close the full-size image
              const closeButton = await page.waitForSelector('.modal-close');
              await closeButton.click();
            
              // Wait for the modal to be hidden
              await page.waitForSelector('.modal-image-container .modal-image', { state: 'hidden' });
              break;
          }
      }
      expect(foundImage).toBeTruthy();
    }
  });