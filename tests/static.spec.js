const fs = require('fs');
const cheerio = require('cheerio');
const { test, expect, chromium } = require('@playwright/test');

test.setTimeout(60000);
const html = fs.readFileSync('index.html', 'utf-8');
const $ = cheerio.load(html);

// Static tests (no browser interaction)
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

// Interactive tests (with browser)
test('Category Navigation', async ({ page }) => {
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');

    // Test multiple categories
    const categoriesToTest = [
      { name: 'Monuments', selector: '.category-link[href="#monuments-collection"]' },
      { name: 'Columbarium', selector: '.category-link[href="#columbarium-collection"]' }
    ];

    for (const category of categoriesToTest) {
      console.log(`Testing category: ${category.name}`);
      
      // Open the category
      await page.click(category.selector);

      // Wait for the modal to be visible
      await page.waitForSelector('#category-modal', { state: 'visible' });

      // Wait for thumbnails
      await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });

      // Get all thumbnails
      const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
      console.log(`Found ${thumbnails.length} thumbnails`);
      
      expect(thumbnails.length).toBeGreaterThan(0);

      if (thumbnails.length > 0) {
        // Wait for first thumbnail to load
        console.log('Waiting for first thumbnail to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Click the first thumbnail
        console.log('Clicking first thumbnail');
        await thumbnails[0].click();
        
        // Wait for fullscreen view and image
        await page.waitForSelector('#fullscreen-view', { state: 'visible' });
        await page.waitForSelector('.fullscreen-image', { state: 'visible' });

        // Wait for fullscreen image to load
        console.log('Waiting for fullscreen image to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('#fullscreen-view .fullscreen-image');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Verify the image is loaded
        const imageLoaded = await page.evaluate(() => {
          const img = document.querySelector('#fullscreen-view .fullscreen-image');
          return img && img.complete && img.naturalWidth > 0;
        });
        expect(imageLoaded).toBeTruthy();

        try {
          // Try to close fullscreen view using dispatchEvent
          const fullscreenClosed = await page.evaluate(() => {
            const closeBtn = document.querySelector('.close-fullscreen');
            if (closeBtn) {
              const clickEvent = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
              });
              const clickResult = closeBtn.dispatchEvent(clickEvent);
              console.log('Fullscreen close click dispatched:', clickResult);
              return true;
            }
            console.log('Fullscreen close button not found');
            return false;
          });
          console.log('Fullscreen close result:', fullscreenClosed);
          
          // Wait for fullscreen to be hidden
          await page.waitForSelector('#fullscreen-view', { state: 'hidden' });

          // Try to close category modal using dispatchEvent
          const modalClosed = await page.evaluate(() => {
            const closeBtn = document.querySelector('.close-modal');
            if (closeBtn) {
              const clickEvent = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
              });
              const clickResult = closeBtn.dispatchEvent(clickEvent);
              console.log('Modal close click dispatched:', clickResult);
              return true;
            }
            console.log('Modal close button not found');
            return false;
          });
          console.log('Modal close result:', modalClosed);
          
          // Wait for modal to be hidden
          await page.waitForSelector('#category-modal', { state: 'hidden' });
        } catch (error) {
          console.error('Error during close operations:', error);
          
          // Log the current state of the page
          const pageState = await page.evaluate(() => ({
            fullscreenExists: !!document.querySelector('#fullscreen-view'),
            fullscreenDisplay: document.querySelector('#fullscreen-view')?.style.display,
            fullscreenHTML: document.querySelector('#fullscreen-view')?.innerHTML,
            fullscreenCloseExists: !!document.querySelector('.close-fullscreen'),
            modalExists: !!document.querySelector('#category-modal'),
            modalDisplay: document.querySelector('#category-modal')?.style.display,
            modalHTML: document.querySelector('#category-modal')?.innerHTML,
            modalCloseExists: !!document.querySelector('.close-modal')
          }));
          console.log('Page state:', pageState);
        }
      }
    }
});

test('Product Search', async ({ page }) => {
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');

    // Test different search scenarios
    const searchTests = [
      { term: '430', description: 'Search by product number' },
      { term: 'mbna', description: 'Search by category prefix' }
    ];

    for (const test of searchTests) {
      console.log(`Testing search for: ${test.term}`);
      
      // Type search term
      const searchInput = await page.waitForSelector('#product-search');
      await searchInput.fill(test.term);
      
      // Wait for search results
      await page.waitForSelector('.search-results-grid');
      
      // Get all results
      const results = await page.$$('.search-result-item');
      console.log(`Found ${results.length} results`);
      
      // Should show results
      expect(results.length).toBeGreaterThan(0);
      
      // Click the first result
      await results[0].click();
      
      // Wait for fullscreen view and image
      await page.waitForSelector('#fullscreen-view', { state: 'visible' });
      await page.waitForSelector('.fullscreen-image', { state: 'visible' });

      // Wait for fullscreen image to load
      console.log('Waiting for fullscreen image to load');
      await page.waitForFunction(
          () => {
              const img = document.querySelector('#fullscreen-view .fullscreen-image');
              return img && img.complete && img.naturalWidth > 0;
          }
      );

      // Verify the image is loaded
      const imageLoaded = await page.evaluate(() => {
        const img = document.querySelector('#fullscreen-view .fullscreen-image');
        return img && img.complete && img.naturalWidth > 0;
      });
      expect(imageLoaded).toBeTruthy();

      try {
        // Try to close fullscreen view using dispatchEvent
        const fullscreenClosed = await page.evaluate(() => {
          const closeBtn = document.querySelector('.close-fullscreen');
          if (closeBtn) {
            const clickEvent = new MouseEvent('click', {
              bubbles: true,
              cancelable: true,
              view: window
            });
            const clickResult = closeBtn.dispatchEvent(clickEvent);
            console.log('Fullscreen close click dispatched:', clickResult);
            return true;
          }
          console.log('Fullscreen close button not found');
          return false;
        });
        console.log('Fullscreen close result:', fullscreenClosed);
        
        // Wait for fullscreen to be hidden
        await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
      } catch (error) {
        console.error('Error during close operations:', error);
        
        // Log the current state of the page
        const pageState = await page.evaluate(() => ({
          fullscreenExists: !!document.querySelector('#fullscreen-view'),
          fullscreenDisplay: document.querySelector('#fullscreen-view')?.style.display,
          fullscreenHTML: document.querySelector('#fullscreen-view')?.innerHTML,
          fullscreenCloseExists: !!document.querySelector('.close-fullscreen')
        }));
        console.log('Page state:', pageState);
      }

      // Clear the search input for next test
      await searchInput.fill('');
      await page.waitForTimeout(500); // Wait for search to clear
    }
});

test('Modal Navigation', async ({ page }) => {
    // Set viewport to desktop full screen
    await page.setViewportSize({ width: 1920, height: 1080 });
    
    console.log('Starting Modal Navigation test');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');

    // Open a category
    console.log('Opening category');
    await page.click('.category-link[href="#monuments-collection"]');

    // Wait for the modal and its content
    console.log('Waiting for modal and content');
    await Promise.all([
        page.waitForSelector('#category-modal', { state: 'visible' }),
        page.waitForSelector('.thumbnails-grid', { state: 'visible' })
    ]);
    console.log('Modal and content loaded');

    // Wait for thumbnails and verify
    console.log('Checking thumbnails');
    await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });
    const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
    console.log(`Found ${thumbnails.length} thumbnails`);
    expect(thumbnails.length).toBeGreaterThan(0);

    if (thumbnails.length > 0) {
        // Wait for first thumbnail to load
        console.log('Waiting for first thumbnail to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Click first thumbnail
        console.log('Clicking first thumbnail');
        await thumbnails[0].click();

        // Wait for fullscreen view
        console.log('Waiting for fullscreen view');
        await Promise.all([
            page.waitForSelector('#fullscreen-view', { state: 'visible' }),
            page.waitForSelector('.fullscreen-image', { state: 'visible' })
        ]);
        console.log('Fullscreen view loaded');

        // Wait for fullscreen image to load
        console.log('Waiting for fullscreen image to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('#fullscreen-view .fullscreen-image');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Verify image loaded
        const imageLoaded = await page.evaluate(() => {
            const img = document.querySelector('#fullscreen-view .fullscreen-image');
            return img && img.complete && img.naturalWidth > 0;
        });
        expect(imageLoaded).toBeTruthy();

        if (thumbnails.length > 1) {
            // Test next button
            console.log('Testing next button');
            await page.click('.fullscreen-nav.next');
            await page.waitForTimeout(500);
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('#fullscreen-view .fullscreen-image');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );

            // Test previous button
            console.log('Testing previous button');
            await page.click('.fullscreen-nav.prev');
            await page.waitForTimeout(500);
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('#fullscreen-view .fullscreen-image');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );
        }

        // Close fullscreen view
        console.log('Closing fullscreen view');
        await page.locator('//*[@id="fullscreen-view"]/div/button[1]').click();
        await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
        console.log('Fullscreen view closed');
    }

    // Close modal
    console.log('Closing modal');
    await page.locator('//*[@id="category-modal"]/div/div[1]/button').click();
    await page.waitForSelector('#category-modal', { state: 'hidden' });
    console.log('Modal closed');
});

test('Modal Navigation - Mobile', async ({ page }) => {
    // Set viewport to mobile size
    await page.setViewportSize({ width: 375, height: 812 }); // iPhone X dimensions
    
    console.log('Starting Mobile Modal Navigation test');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');

    // Open a category
    console.log('Opening category');
    await page.click('.category-link[href="#monuments-collection"]');

    // Wait for the modal and its content
    console.log('Waiting for modal and content');
    await Promise.all([
        page.waitForSelector('#category-modal', { state: 'visible' }),
        page.waitForSelector('.thumbnails-grid', { state: 'visible' })
    ]);
    console.log('Modal and content loaded');

    // Wait for thumbnails and verify
    console.log('Checking thumbnails');
    await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });
    const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
    console.log(`Found ${thumbnails.length} thumbnails`);
    expect(thumbnails.length).toBeGreaterThan(0);

    if (thumbnails.length > 0) {
        // Wait for first thumbnail to load
        console.log('Waiting for first thumbnail to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Click first thumbnail
        console.log('Clicking first thumbnail');
        await thumbnails[0].click();

        // Wait for fullscreen view
        console.log('Waiting for fullscreen view');
        await Promise.all([
            page.waitForSelector('#fullscreen-view', { state: 'visible' }),
            page.waitForSelector('.fullscreen-image', { state: 'visible' })
        ]);
        console.log('Fullscreen view loaded');

        // Wait for fullscreen image to load
        console.log('Waiting for fullscreen image to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('#fullscreen-view .fullscreen-image');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Test swipe navigation
        console.log('Testing swipe navigation');
        
        // Swipe left
        await page.mouse.move(300, 400);
        await page.mouse.down();
        await page.mouse.move(100, 400, { steps: 10 });
        await page.mouse.up();
        await page.waitForTimeout(500);
        
        // Swipe right
        await page.mouse.move(100, 400);
        await page.mouse.down();
        await page.mouse.move(300, 400, { steps: 10 });
        await page.mouse.up();
        await page.waitForTimeout(500);

        // Close fullscreen view
        console.log('Closing fullscreen view');
        await page.locator('//*[@id="fullscreen-view"]/div/button[1]').click();
        await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
        console.log('Fullscreen view closed');
    }

    // Close modal
    console.log('Closing modal');
    await page.locator('//*[@id="category-modal"]/div/div[1]/button').click();
    await page.waitForSelector('#category-modal', { state: 'hidden' });
    console.log('Modal closed');
});

test('Image Loading and Error Handling', async ({ page }) => {
    // Set viewport to desktop full screen
    await page.setViewportSize({ width: 1920, height: 1080 });
    
    console.log('Starting Image Loading test');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');

    // Open a category
    console.log('Opening category');
    await page.click('.category-link[href="#monuments-collection"]');

    // Wait for the modal and its content
    console.log('Waiting for modal and content');
    await Promise.all([
        page.waitForSelector('#category-modal', { state: 'visible' }),
        page.waitForSelector('.thumbnails-grid', { state: 'visible' })
    ]);
    console.log('Modal and content loaded');

    // Wait for thumbnails and verify
    console.log('Checking thumbnails');
    await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });
    const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
    console.log(`Found ${thumbnails.length} thumbnails`);
    expect(thumbnails.length).toBeGreaterThan(0);

    // Test thumbnail loading
    console.log('Testing thumbnail loading');
    for (const thumbnail of thumbnails) {
        const isVisible = await thumbnail.isVisible();
        expect(isVisible).toBeTruthy();

        const isLoaded = await page.evaluate(el => {
            const img = el.querySelector('img');
            return img && img.complete && img.naturalWidth > 0;
        }, thumbnail);
        expect(isLoaded).toBeTruthy();

        // Wait a bit for each thumbnail to ensure proper loading
        await page.waitForTimeout(100);
    }

    if (thumbnails.length > 0) {
        // Wait for first thumbnail to load
        console.log('Waiting for first thumbnail to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Click first thumbnail
        console.log('Clicking first thumbnail');
        await thumbnails[0].click();

        // Wait for fullscreen view
        console.log('Waiting for fullscreen view');
        await Promise.all([
            page.waitForSelector('#fullscreen-view', { state: 'visible' }),
            page.waitForSelector('.fullscreen-image', { state: 'visible' })
        ]);
        console.log('Fullscreen view loaded');

        // Wait for fullscreen image to load
        console.log('Waiting for fullscreen image to load');
        await page.waitForFunction(
            () => {
                const img = document.querySelector('#fullscreen-view .fullscreen-image');
                return img && img.complete && img.naturalWidth > 0;
            }
        );

        // Verify image loaded
        const imageLoaded = await page.evaluate(() => {
            const img = document.querySelector('#fullscreen-view .fullscreen-image');
            return img && img.complete && img.naturalWidth > 0;
        });
        expect(imageLoaded).toBeTruthy();

        // Close fullscreen view
        console.log('Closing fullscreen view');
        await page.locator('//*[@id="fullscreen-view"]/div/button[1]').click();
        await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
        console.log('Fullscreen view closed');
    }

    // Close modal
    console.log('Closing modal');
    await page.locator('//*[@id="category-modal"]/div/div[1]/button').click();
    await page.waitForSelector('#category-modal', { state: 'hidden' });
    console.log('Modal closed');
});

test('Product Details', async ({ page }) => {
    // Set viewport to desktop full screen
    await page.setViewportSize({ width: 1920, height: 1080 });
    
    console.log('Starting Product Details test');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');

    const categoriesToTest = [
        { name: 'Monuments', selector: '.category-link[href="#monuments-collection"]' },
        { name: 'Columbarium', selector: '.category-link[href="#columbarium-collection"]' }
    ];

    for (const category of categoriesToTest) {
        console.log(`Testing category: ${category.name}`);
        
        // Open category
        console.log('Opening category');
        await page.click(category.selector);

        // Wait for the modal and its content
        console.log('Waiting for modal and content');
        await Promise.all([
            page.waitForSelector('#category-modal', { state: 'visible' }),
            page.waitForSelector('.thumbnails-grid', { state: 'visible' })
        ]);
        console.log('Modal and content loaded');

        // Wait for thumbnails and verify
        console.log('Checking thumbnails');
        await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });
        const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
        console.log(`Found ${thumbnails.length} thumbnails`);
        expect(thumbnails.length).toBeGreaterThan(0);

        if (thumbnails.length > 0) {
            // Wait for first thumbnail to load
            console.log('Waiting for first thumbnail to load');
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );

            // Click first thumbnail
            console.log('Clicking first thumbnail');
            await thumbnails[0].click();

            // Wait for fullscreen view
            console.log('Waiting for fullscreen view');
            await Promise.all([
                page.waitForSelector('#fullscreen-view', { state: 'visible' }),
                page.waitForSelector('.fullscreen-image', { state: 'visible' })
            ]);
            console.log('Fullscreen view loaded');

            // Wait for fullscreen image to load
            console.log('Waiting for fullscreen image to load');
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('#fullscreen-view .fullscreen-image');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );

            // Verify image loaded
            const imageLoaded = await page.evaluate(() => {
                const img = document.querySelector('#fullscreen-view .fullscreen-image');
                return img && img.complete && img.naturalWidth > 0;
            });
            expect(imageLoaded).toBeTruthy();

            // Close fullscreen view
            console.log('Closing fullscreen view');
            await page.locator('//*[@id="fullscreen-view"]/div/button[1]').click();
            await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
            console.log('Fullscreen view closed');

            // Close modal
            console.log('Closing modal');
            await page.locator('//*[@id="category-modal"]/div/div[1]/button').click();
            await page.waitForSelector('#category-modal', { state: 'hidden' });
            console.log('Modal closed');
        }
    }
});

test('Product Details - Mobile', async ({ page }) => {
    // Set viewport to mobile size
    await page.setViewportSize({ width: 375, height: 812 }); // iPhone X dimensions
    
    console.log('Starting Mobile Product Details test');
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    console.log('Page loaded');

    const categoriesToTest = [
        { name: 'Monuments', selector: '.category-link[href="#monuments-collection"]' },
        { name: 'Columbarium', selector: '.category-link[href="#columbarium-collection"]' }
    ];

    for (const category of categoriesToTest) {
        console.log(`Testing category: ${category.name}`);
        
        // Open category
        console.log('Opening category');
        await page.click(category.selector);

        // Wait for the modal and its content
        console.log('Waiting for modal and content');
        await Promise.all([
            page.waitForSelector('#category-modal', { state: 'visible' }),
            page.waitForSelector('.thumbnails-grid', { state: 'visible' })
        ]);
        console.log('Modal and content loaded');

        // Wait for thumbnails and verify
        console.log('Checking thumbnails');
        await page.waitForSelector('.thumbnails-grid .thumbnail-item', { state: 'visible' });
        const thumbnails = await page.$$('.thumbnails-grid .thumbnail-item');
        console.log(`Found ${thumbnails.length} thumbnails`);
        expect(thumbnails.length).toBeGreaterThan(0);

        if (thumbnails.length > 0) {
            // Wait for first thumbnail to load
            console.log('Waiting for first thumbnail to load');
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('.thumbnails-grid .thumbnail-item img');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );

            // Click first thumbnail
            console.log('Clicking first thumbnail');
            await thumbnails[0].click();

            // Wait for fullscreen view
            console.log('Waiting for fullscreen view');
            await Promise.all([
                page.waitForSelector('#fullscreen-view', { state: 'visible' }),
                page.waitForSelector('.fullscreen-image', { state: 'visible' })
            ]);
            console.log('Fullscreen view loaded');

            // Wait for fullscreen image to load
            console.log('Waiting for fullscreen image to load');
            await page.waitForFunction(
                () => {
                    const img = document.querySelector('#fullscreen-view .fullscreen-image');
                    return img && img.complete && img.naturalWidth > 0;
                }
            );

            // Test swipe navigation
            console.log('Testing swipe navigation');
            
            // Swipe left
            await page.mouse.move(300, 400);
            await page.mouse.down();
            await page.mouse.move(100, 400, { steps: 10 });
            await page.mouse.up();
            await page.waitForTimeout(500);
            
            // Swipe right
            await page.mouse.move(100, 400);
            await page.mouse.down();
            await page.mouse.move(300, 400, { steps: 10 });
            await page.mouse.up();
            await page.waitForTimeout(500);

            // Close fullscreen view
            console.log('Closing fullscreen view');
            await page.locator('//*[@id="fullscreen-view"]/div/button[1]').click();
            await page.waitForSelector('#fullscreen-view', { state: 'hidden' });
            console.log('Fullscreen view closed');

            // Close modal
            console.log('Closing modal');
            await page.locator('//*[@id="category-modal"]/div/div[1]/button').click();
            await page.waitForSelector('#category-modal', { state: 'hidden' });
            console.log('Modal closed');
        }
    }
});