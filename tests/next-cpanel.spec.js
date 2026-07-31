const { test, expect } = require("@playwright/test");

test.describe("Next.js cPanel public site", () => {
  test("featured products and color preview images render without CRM calls", async ({ page }) => {
    const crmRequests = [];
    page.on("request", (request) => {
      if (/\/crm(?:\/|$)/i.test(new URL(request.url()).pathname)) {
        crmRequests.push(request.url());
      }
    });

    await page.goto("http://127.0.0.1:8089/", { waitUntil: "networkidle" });
    const heroVideo = page.locator("video.hero-video");
    await expect(heroVideo).toHaveCount(1);
    await expect
      .poll(() => heroVideo.evaluate((video) => video.currentTime))
      .toBeGreaterThan(0);
    await expect(heroVideo).toHaveJSProperty("paused", false);
    expect(await heroVideo.evaluate((video) => video.currentSrc)).toMatch(
      /\/images\/as\.(?:webm|mp4)$/
    );
    const featured = page.locator(".featured-card img");
    await expect(featured).toHaveCount(5);
    await expect(page.locator(".featured-card strong")).toHaveText([
      "Benches",
      "In-stock, ready to ship special designs",
      "Columbarium",
      "Designs",
      "MBNA 2025"
    ]);
    await expect(page.locator(".featured-card .featured-copy small")).toHaveCount(0);
    await expect(page.locator(".featured-grid")).not.toContainText(
      /\b\d+\s+designs?\b/i
    );
    for (let index = 0; index < (await featured.count()); index += 1) {
      await expect(featured.nth(index)).toHaveJSProperty("complete", true);
      expect(await featured.nth(index).evaluate((image) => image.naturalWidth)).toBeGreaterThan(0);
    }

    const colorPreview = page.locator(".color-strip img");
    await expect(colorPreview).toHaveCount(8);
    for (let index = 0; index < (await colorPreview.count()); index += 1) {
      await colorPreview.nth(index).scrollIntoViewIfNeeded();
      await expect
        .poll(() => colorPreview.nth(index).evaluate((image) => image.naturalWidth))
        .toBeGreaterThan(0);
      await expect(colorPreview.nth(index)).toHaveAttribute(
        "alt",
        /polished stone color sample$/i
      );
    }
    await expect(page.locator(".brand img")).toHaveAttribute(
      "alt",
      "Angel Granites logo"
    );
    await expect(page.locator(".brand")).toHaveAttribute(
      "aria-label",
      "Angel Granites home"
    );
    expect(crmRequests).toEqual([]);
  });

  test("all granite color cards decode their resolved images", async ({ page }) => {
    await page.goto("http://127.0.0.1:8089/granite-colors/", {
      waitUntil: "networkidle"
    });
    const cards = page.locator(".color-card");
    await expect(cards).toHaveCount(63);
    await expect(page.locator(".color-family-grid article")).toHaveCount(6);
    await expect(page.locator("h1")).toContainText("Granite colors");
    const images = cards.locator("img");
    const imageSources = await images.evaluateAll((elements) =>
      elements.map((image) => new URL(image.src).pathname.toLowerCase())
    );
    expect(new Set(imageSources).size).toBe(imageSources.length);
    for (let index = 0; index < (await images.count()); index += 1) {
      await images.nth(index).scrollIntoViewIfNeeded();
      await expect
        .poll(() => images.nth(index).evaluate((image) => image.naturalWidth))
        .toBeGreaterThan(0);
    }
  });

  test("color catalog safely merges new images from the preserved mobile API", async ({
    page
  }) => {
    const requests = [];
    await page.route("**/get_color_images.php", async (route) => {
      requests.push({
        method: route.request().method(),
        pathname: new URL(route.request().url()).pathname
      });
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          count: 3,
          colors: [
            {
              name: "Bahama Blue",
              path: "images/colors/Bahama%20Blue.webp"
            },
            {
              name: "Test Gold",
              path: "images/colors/Rustic%20Brown.webp"
            },
            {
              name: "PICASSO",
              path: "images/colors/PICASSO.webp"
            }
          ]
        })
      });
    });

    await page.goto("http://127.0.0.1:8089/granite-colors/", {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".color-card")).toHaveCount(64);
    await expect(page.getByText("Test Gold Granite", { exact: true })).toBeVisible();
    await expect(page.getByText("PICASSO Granite", { exact: true })).toHaveCount(0);
    await expect(
      page.getByRole("link", { name: /Test Gold Granite/ })
    ).toHaveAttribute("href", /\/inventory\/\?search=Test(?:%20|\+)Gold/);
    expect(requests).toEqual([
      { method: "GET", pathname: "/get_color_images.php" }
    ]);
  });

  test("inventory loads from the inventory API and filters live stock", async ({ page }) => {
    await page.setViewportSize({ width: 1600, height: 1000 });
    const inventoryRequests = [];
    const directoryRequests = [];
    await page.route("**/inventory-proxy.php?**", async (route) => {
      inventoryRequests.push(route.request().url());
      const requestUrl = new URL(route.request().url());
      if (requestUrl.searchParams.get("action") === "getDetails") {
        await route.fulfill({
          contentType: "application/json",
          body: JSON.stringify({
            success: true,
            stones: [
              {
                Container: "AS-17",
                CrateNo: "5",
                Status: "In-Stock",
                StockId: "254518",
                LocationName: "Elberton",
                SublocationName: "Warehouse",
                Weight: "875"
              }
            ]
          })
        });
        return;
      }
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            {
              Qty: 3,
              EndProductCode: "AG-298-INDIA-RED",
              EndProductDescription: "Serp Top Monument",
              Ptype: "Monument",
              PColor: "India Red",
              PDesign: "AG-298",
              PFinish: "Polished",
              Size: "3-0 X 2-0 X 0-6",
              Locationname: "Elberton"
            },
            {
              Qty: 1000,
              EndProductCode: "AG-B12-GEORGIA-GRAY",
              EndProductDescription: "Granite Bench",
              Ptype: "Bench",
              PColor: "Georgia Gray",
              PDesign: "AG-B12",
              PFinish: "Steeled",
              Size: "4-0 X 1-2 X 0-4",
              Locationname: "Barre"
            },
            {
              Qty: 2,
              EndProductCode: "HEART-TABLET",
              EndProductDescription: "Premium Jet Black Tablet, Double Heart",
              Ptype: "Tablet",
              PColor: "Premium Jet Black",
              PDesign: "Double Heart",
              PFinish: "Polished",
              Size: "4-0 X 2-4 X 0-8",
              Locationname: "Elberton"
            },
            {
              Qty: 1,
              EndProductCode: "PLAIN-TABLET",
              EndProductDescription: "Gray serpentine tablet",
              Ptype: "Tablet",
              PColor: "Georgia Gray",
              PDesign: "Serpentine",
              PFinish: "Polished",
              Size: "4-0 X 2-4 X 0-6",
              Locationname: "Elberton"
            }
          ]
        })
      });
    });
    await page.route("**/get_directory_files.php?**", async (route) => {
      const requestUrl = new URL(route.request().url());
      const directory = requestUrl.searchParams.get("directory");
      directoryRequests.push(directory);
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          files:
            directory === "products"
              ? [
                  {
                    name: "Benches",
                    path: "images/products/Benches"
                  }
                ]
              : [
                  {
                    name: "AG-298",
                    path: "images/products/Benches/AG-298.jpg?v=1785168000"
                  }
                ]
        })
      });
    });

    await page.goto("http://127.0.0.1:8089/inventory/", {
      waitUntil: "networkidle"
    });
    const inventoryHeroHeight = await page
      .locator(".page-hero--inventory")
      .evaluate((element) => element.getBoundingClientRect().height);
    expect(inventoryHeroHeight).toBeLessThan(390);
    await expect(page.locator(".inventory-result")).toHaveCount(4);
    const inventoryLayout = await page.locator(".inventory-results").evaluate((element) => ({
      columns: getComputedStyle(element).gridTemplateColumns.split(" ").length,
      firstCardHeight: element.firstElementChild.getBoundingClientRect().height
    }));
    expect(inventoryLayout.columns).toBe(4);
    expect(inventoryLayout.firstCardHeight).toBeLessThan(210);
    await expect(page.locator(".inventory-result").first()).toContainText(
      "AG-298"
    );
    await expect(page.locator(".inventory-result").first().locator("img")).toBeVisible();
    await expect(page.locator(".inventory-result").first().locator("img")).toHaveAttribute(
      "src",
      /\?v=1785168000$/
    );
    await expect(page.locator(".inventory-result").first().locator("img")).toHaveAttribute(
      "alt",
      /AG-298 India Red Monument current inventory/
    );
    await expect(page.locator(".inventory-result img[alt='']")).toHaveCount(0);
    await expect(page.locator(".inventory-results")).toContainText("AG-298");
    await page.getByLabel("Search inventory").fill("India Red");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-results")).toContainText("Serp Top Monument");
    await expect(page.locator(".inventory-result img")).toBeVisible();
    await page.getByLabel("Search inventory").fill("Is AG-298 available?");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await page.getByLabel("Search inventory").fill("3-0 x 0-6 x 2-0");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-results")).toContainText("Serp Top Monument");
    await page.getByLabel("Search inventory").fill("Do you have heart head stones?");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-results")).toContainText("Double Heart");
    await page
      .getByLabel("Search inventory")
      .fill("Are any 0-8 thickness stones available?");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-results")).toContainText("Double Heart");
    await page.getByLabel("Search inventory").fill("AG-298");
    await page.locator(".inventory-result").click();
    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(
      page.getByRole("dialog").locator(".inventory-detail-primary img")
    ).toBeVisible();
    await expect(page.getByRole("dialog")).toContainText(
      "Photography may show a different granite color"
    );
    await expect(page.getByRole("dialog")).toContainText("AS-17");
    await expect(page.getByRole("dialog")).toContainText("Crate number");
    await expect(page.getByRole("dialog")).toContainText("254518");
    const detailOrder = await page.getByRole("dialog").evaluate((dialog) => ({
      referenceImage: dialog
        .querySelector(".inventory-detail-primary")
        .getBoundingClientRect().top,
      stoneRecords: dialog
        .querySelector(".inventory-stones")
        .getBoundingClientRect().top
    }));
    expect(detailOrder.referenceImage).toBeLessThan(detailOrder.stoneRecords);
    expect(inventoryRequests).toHaveLength(2);
    expect(new URL(inventoryRequests[0]).pathname).toBe("/inventory-proxy.php");
    expect(new URL(inventoryRequests[1]).searchParams.get("action")).toBe(
      "getDetails"
    );
    expect(directoryRequests).toEqual(["products", "products/Benches"]);
  });

  test("inventory prioritizes exact dimensions and design codes in mixed searches", async ({
    page
  }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.route("**/inventory-proxy.php?**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            {
              Qty: 1,
              EndProductCode: "AG-194-BLACK-EXACT",
              EndProductDescription:
                "Premium Jet Black Tablet, Double Heart, exact 3-0 size",
              Ptype: "Tablet",
              PColor: "Premium Jet Black",
              PDesign: "AG-194",
              PFinish: "P5",
              Size: "3-0 X 0-6 X 2-0",
              Locationname: "Elberton"
            },
            {
              Qty: 50,
              EndProductCode: "REFERENCE-3-0-999",
              EndProductDescription: "Premium Jet Black Tablet, Single Heart",
              Ptype: "Tablet",
              PColor: "Premium Jet Black",
              PDesign: "AG-193",
              PFinish: "P5",
              Size: "2-0 X 0-8 X 2-4",
              Locationname: "Elberton"
            },
            {
              Qty: 20,
              EndProductCode: "AG-298-BLACK",
              EndProductDescription: "Premium Jet Black curved support",
              Ptype: "Support",
              PColor: "Premium Jet Black",
              PDesign: "AG-298",
              PFinish: "P5",
              Size: "3-0 X 0-6 X 2-0",
              Locationname: "Barre"
            }
          ]
        })
      });
    });

    await page.goto("http://127.0.0.1:8089/inventory/", {
      waitUntil: "domcontentloaded"
    });
    await expect(page.locator(".inventory-result")).toHaveCount(3);

    const firstResultTop = await page
      .locator(".inventory-result")
      .first()
      .evaluate((element) => element.getBoundingClientRect().top + window.scrollY);
    expect(firstResultTop).toBeLessThan(900);

    await page.getByLabel("Search inventory").fill("black heart 3-0");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-result").first()).toContainText(
      "exact 3-0 size"
    );

    await page.getByLabel("Search inventory").fill("AG-298 black");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-result").first()).toContainText(
      "AG-298"
    );
  });

  test("inventory loads subsequent API pages after the first 1,000 items", async ({
    page
  }) => {
    const requestedPages = [];
    await page.route("**/inventory-proxy.php?**", async (route) => {
      const requestUrl = new URL(route.request().url());
      const requestedPage = Number(requestUrl.searchParams.get("page") || "1");
      requestedPages.push(requestedPage);
      const data =
        requestedPage === 1
          ? Array.from({ length: 1000 }, (_, index) => ({
              c: `PAGED-${index + 1}`,
              d: `Paged inventory item ${index + 1}`,
              t: "Tablet",
              g: "Georgia Gray",
              n: "",
              f: "P5",
              s: "3-0 X 0-6 X 2-0",
              l: "Elberton",
              q: "1"
            }))
          : requestedPage === 2
            ? [
                {
                  c: "PAGED-1001",
                  d: "Paged inventory item 1001",
                  t: "Tablet",
                  g: "Georgia Gray",
                  n: "",
                  f: "P5",
                  s: "3-0 X 0-6 X 2-0",
                  l: "Elberton",
                  q: "1"
                }
              ]
            : [];
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({ success: true, data })
      });
    });

    await page.goto("http://127.0.0.1:8089/inventory/", {
      waitUntil: "networkidle"
    });

    await expect(page.locator(".inventory-summary")).toContainText(
      "1001 inventory items"
    );
    expect(requestedPages).toEqual([1, 2]);
  });

  test("catalog, color detail, contact and mobile navigation render", async ({ page }) => {
    await page.route("**/get_directory_files.php?**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          files: [
            {
              name: "AG-840",
              path: "images/products/Monuments/AG-840.jpg?v=1785168000"
            }
          ]
        })
      });
    });
    await page.goto("http://127.0.0.1:8089/monuments/", {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".product-card").first()).toBeVisible();
    await expect(page.locator(".product-card img").first()).toHaveJSProperty("complete", true);
    await expect(
      page.locator('.product-card img[src*="AG-840.jpg?v=1785168000"]')
    ).toBeVisible();
    const curatedCard = page.locator('a.product-card[href^="/designs/"]').first();
    await expect(curatedCard).toBeVisible();
    const designHref = await curatedCard.getAttribute("href");
    await page.goto(`http://127.0.0.1:8089${designHref}`, {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".design-detail h1")).toBeVisible();
    await expect(page.locator(".design-detail figure img")).toHaveJSProperty("complete", true);
    await expect(page.getByText("Reference configurations are shown")).toBeVisible();
    await expect(
      page.getByRole("link", { name: /^Previous design:/ })
    ).toHaveAttribute("rel", "prev");
    await expect(
      page.getByRole("link", { name: /^Next design:/ })
    ).toHaveAttribute("rel", "next");

    await page.goto("http://127.0.0.1:8089/colors/rain-forest-green-granite/", {
      waitUntil: "networkidle"
    });
    const colorImage = page.locator(".color-detail figure img");
    await expect(colorImage).toBeVisible();
    expect(await colorImage.evaluate((image) => image.naturalWidth)).toBeGreaterThan(0);
    await expect(page.getByRole("link", { name: "Search inventory by color" })).toHaveAttribute(
      "href",
      /\/inventory\/\?search=/
    );
    const previousColor = page.getByRole("link", {
      name: /^Previous color:/
    });
    const nextColor = page.getByRole("link", { name: /^Next color:/ });
    await expect(previousColor).toBeVisible();
    await expect(nextColor).toBeVisible();
    await expect(previousColor).toHaveAttribute("rel", "prev");
    await expect(nextColor).toHaveAttribute("rel", "next");
    const nextColorHref = await nextColor.getAttribute("href");

    await page.setViewportSize({ width: 390, height: 844 });
    for (const control of [previousColor, nextColor]) {
      const box = await control.boundingBox();
      expect(box?.width).toBeGreaterThanOrEqual(48);
      expect(box?.height).toBeGreaterThanOrEqual(48);
    }
    await nextColor.click();
    await expect(page).toHaveURL(
      `http://127.0.0.1:8089${nextColorHref}`
    );
    await expect(page.locator(".color-detail h1")).not.toHaveText(
      "Rain Forest Green Granite"
    );

    await page.goto("http://127.0.0.1:8089/contact/", {
      waitUntil: "domcontentloaded"
    });
    await expect(page.locator("form[action='/contact-submit.php']")).toBeVisible();
    await expect(page.locator(".contact-list")).toContainText(
      "15 Blackwell St, Barre, VT 05641"
    );

    await page.goto("http://127.0.0.1:8089/", { waitUntil: "domcontentloaded" });
    const menu = page.locator(".menu-toggle");
    await expect(menu).toBeVisible();
    await menu.click();
    await expect(page.locator("#primary-navigation")).toHaveClass(/is-open/);
  });

  test("collection lightbox supports sequential mobile and keyboard navigation", async ({
    page
  }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("http://127.0.0.1:8089/benches/", {
      waitUntil: "networkidle"
    });

    const trigger = page.getByRole("button", {
      name: /^View .* larger$/
    }).first();
    await trigger.click();

    const dialog = page.getByRole("dialog");
    await expect(dialog).toBeVisible();
    await expect(dialog.getByText(/Design \d+ of \d+/)).toBeVisible();
    const previousImage = dialog.getByRole("button", {
      name: /^Previous image:/
    });
    const nextImage = dialog.getByRole("button", { name: /^Next image:/ });
    for (const control of [previousImage, nextImage]) {
      await expect(control).toBeVisible();
      const box = await control.boundingBox();
      expect(box?.width).toBeGreaterThanOrEqual(48);
      expect(box?.height).toBeGreaterThanOrEqual(48);
    }

    const initialLabel = await dialog.getAttribute("aria-label");
    await page.keyboard.press("ArrowRight");
    await expect(dialog).not.toHaveAttribute("aria-label", initialLabel);
    const afterKeyboardLabel = await dialog.getAttribute("aria-label");
    await previousImage.click();
    await expect(dialog).not.toHaveAttribute("aria-label", afterKeyboardLabel);

    await page.keyboard.press("Escape");
    await expect(dialog).toHaveCount(0);
    await expect(trigger).toBeFocused();
  });

  test("color detail closes back to the visitor's filtered color catalog", async ({
    page
  }) => {
    await page.route("**/get_color_images.php**", (route) => route.abort());
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("http://127.0.0.1:8089/granite-colors/", {
      waitUntil: "networkidle"
    });

    const search = page.getByRole("searchbox", {
      name: "Search granite colors"
    });
    await search.fill("Premium Black");
    const colorCard = page.locator(".color-card").filter({
      hasText: "Premium Black"
    });
    await expect(colorCard).toHaveCount(1);
    await colorCard.click();

    const closeColor = page.getByRole("link", {
      name: "Close color detail and return to granite colors"
    });
    await expect(closeColor).toBeVisible();
    const closeBox = await closeColor.boundingBox();
    expect(closeBox?.width).toBeGreaterThanOrEqual(48);
    expect(closeBox?.height).toBeGreaterThanOrEqual(48);
    await closeColor.click();

    await expect(page).toHaveURL("http://127.0.0.1:8089/granite-colors/");
    await expect(search).toHaveValue("Premium Black");
    await expect(colorCard).toHaveCount(1);

    await colorCard.click();
    await page.keyboard.press("Escape");
    await expect(page).toHaveURL("http://127.0.0.1:8089/granite-colors/");
    await expect(search).toHaveValue("Premium Black");
  });

  test("Silk Blue and Blue Silk resolve to one canonical SEO color page", async ({
    page
  }) => {
    await page.route("**/inventory-proxy.php?**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({ success: true, data: [] })
      });
    });
    await page.route("**/get_color_images.php**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          colors: [
            {
              name: "Blue Silk",
              path: "images/colors/Blue Silk.jpg"
            }
          ]
        })
      });
    });
    await page.goto("http://127.0.0.1:8089/granite-colors/", {
      waitUntil: "networkidle"
    });
    const search = page.getByRole("searchbox", {
      name: "Search granite colors"
    });
    for (const query of ["Silk Blue", "Blue Silk"]) {
      await search.fill(query);
      const results = page.locator(".color-card");
      await expect(results).toHaveCount(1);
      await expect(results).toContainText("Silk Blue Granite");
      await expect(results).toHaveAttribute(
        "href",
        "/colors/blue-silk-granite/"
      );
    }

    await page.locator(".color-card").click();
    await expect(page).toHaveTitle(
      "Silk Blue Granite for Monuments & Headstones | Angel Granites"
    );
    await expect(page.locator("h1")).toHaveText("Silk Blue Granite");
    await expect(page.getByText("Blue Silk Granite", { exact: true })).toBeVisible();
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
      "href",
      "https://www.theangelstones.com/colors/blue-silk-granite/"
    );
    const structuredData = (
      await page
        .locator('script[type="application/ld+json"]')
        .allTextContents()
    ).map((value) => JSON.parse(value));
    const itemPage = structuredData.find((item) => item["@type"] === "ItemPage");
    expect(itemPage.about.alternateName).toBe("Blue Silk Granite");
  });

  test("inventory deep links prefill the intelligent search", async ({ page }) => {
    await page.route("**/inventory-proxy.php?**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            {
              Qty: 3,
              EndProductCode: "AG-396-BLACK",
              EndProductDescription: "Carved Angel Heart Monument",
              Ptype: "Tablet",
              PColor: "Premium Jet Black",
              PDesign: "AG-396",
              PFinish: "P5",
              Size: "3-0 X 0-8 X 3-0",
              Locationname: "Elberton"
            }
          ]
        })
      });
    });
    await page.goto("http://127.0.0.1:8089/inventory/?search=AG-396", {
      waitUntil: "networkidle"
    });
    await expect(page.getByLabel("Search inventory")).toHaveValue("AG-396");
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    await expect(page.locator(".inventory-result")).toContainText("AG-396");
  });

  test("products, Barre location and learning-center SEO pages are present", async ({
    page
  }) => {
    await page.goto("http://127.0.0.1:8089/products-services/", {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".service-card")).toHaveCount(4);
    await expect(page.locator("main")).toContainText("Sandblasting services");
    await expect(page.locator("main")).toContainText("Custom etching");
    await expect(page.locator(".service-card > span")).toHaveText([
      "01",
      "02",
      "03",
      "04"
    ]);
    const serviceMarkerStyle = await page
      .locator(".service-card > span")
      .first()
      .evaluate((element) => {
        const marker = getComputedStyle(element);
        const divider = getComputedStyle(element, "::after");
        return {
          display: marker.display,
          fontSize: marker.fontSize,
          dividerWidth: divider.width,
          dividerHeight: divider.height
        };
      });
    expect(serviceMarkerStyle).toEqual({
      display: "inline-flex",
      fontSize: "12px",
      dividerWidth: "28px",
      dividerHeight: "1px"
    });

    await page.goto("http://127.0.0.1:8089/locations/barre-vt/", {
      waitUntil: "networkidle"
    });
    await expect(page.locator("h1")).toContainText("Barre, Vermont");
    await expect(page.locator("main")).toContainText(
      "15 Blackwell St, Barre, VT 05641"
    );
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
      "href",
      "https://www.theangelstones.com/locations/barre-vt/"
    );

    const resources = [
      ["monument-glossary", "Granite Monument Glossary"],
      ["common-monument-shapes", "Common Granite Monument Shapes"],
      ["granite-monument-finishes", "Basic Granite Monument Finishes"],
      [
        "how-to-read-monument-dimensions",
        "How to Read Granite Monument Dimensions"
      ],
      [
        "granite-monument-ordering-checklist",
        "Granite Monument Ordering Checklist"
      ]
    ];
    for (const [slug, title] of resources) {
      await page.goto(`http://127.0.0.1:8089/resources/${slug}/`, {
        waitUntil: "networkidle"
      });
      await expect(page.locator("h1")).toHaveText(title);
      await expect(page.locator('script[type="application/ld+json"]')).toHaveCount(3);
    }

    const sitemap = await (
      await page.request.get("http://127.0.0.1:8089/sitemap.xml")
    ).text();
    expect(sitemap).toContain("/products-services/");
    expect(sitemap).toContain("/locations/barre-vt/");
    expect(sitemap).toContain("/resources/monument-glossary/");
  });

  test("legal pages use the shared themed site layout and clean canonicals", async ({
    page
  }) => {
    const legalPages = [
      ["/privacy-policy/", "Privacy Policy"],
      ["/terms-of-service/", "Terms of Service"],
      ["/sms-terms/", "SMS Terms and Conditions"]
    ];
    for (const [route, title] of legalPages) {
      await page.goto(`http://127.0.0.1:8089${route}`, {
        waitUntil: "domcontentloaded"
      });
      await expect(page.locator(".site-header")).toBeVisible();
      await expect(page.locator(".site-footer")).toBeVisible();
      await expect(page.locator("h1")).toHaveText(title);
      await expect(page.locator(".legal-document")).toBeVisible();
      await expect(page.locator(".legal-toc")).toBeVisible();
      await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
        "href",
        `https://www.theangelstones.com${route}`
      );
    }
    const sitemap = await (
      await page.request.get("http://127.0.0.1:8089/sitemap.xml")
    ).text();
    expect(sitemap).toContain("/privacy-policy/");
    expect(sitemap).toContain("/terms-of-service/");
    expect(sitemap).toContain("/sms-terms/");
    expect(sitemap).not.toContain("/privacy-policy.html");
  });

  test("public pages retain the dark Angel Granites theme", async ({ page }) => {
    await page.addInitScript(() => {
      window.localStorage.setItem("angel-theme", "dark");
    });
    await page.goto("http://127.0.0.1:8089/", { waitUntil: "domcontentloaded" });
    const bodyBackground = await page.locator("body").evaluate(
      (element) => getComputedStyle(element).backgroundColor
    );
    const headerBackground = await page.locator(".site-header").evaluate(
      (element) => getComputedStyle(element).backgroundColor
    );
    expect(bodyBackground).toBe("rgb(16, 16, 16)");
    expect(headerBackground).not.toBe("rgb(250, 248, 242)");
  });

  test("payment, flyers, sticky navigation and chat support are restored", async ({
    page
  }) => {
    await page.route("https://embed.tawk.to/**", (route) => route.abort());
    await page.goto("http://127.0.0.1:8089/", { waitUntil: "domcontentloaded" });

    const payment = page.getByRole("link", { name: "Pay invoice" }).first();
    await expect(payment).toHaveAttribute(
      "href",
      "https://link.clover.com/urlshortener/SjQ2Lm"
    );
    await expect(payment).toHaveAttribute("target", "_blank");

    const primaryNavigation = page.locator("#primary-navigation");
    const navigationLinks = primaryNavigation.getByRole("link");
    await expect(navigationLinks.first()).toHaveText("Home");
    await expect(navigationLinks.first()).toHaveAttribute("href", "/");

    const footerEmail = page.locator(
      '.footer-contact-link[href="mailto:info@theangelstones.com"]'
    );
    await expect(footerEmail).toBeVisible();
    await expect(footerEmail).toHaveCSS("text-decoration-line", "underline");

    const footerPayment = page.locator(".footer-payment-link");
    await expect(footerPayment).toHaveAttribute(
      "href",
      "https://link.clover.com/urlshortener/SjQ2Lm"
    );
    await expect(footerPayment).toHaveCSS("min-height", "44px");
    await expect(page.locator(".footer-logo")).toHaveCSS("filter", "none");

    const header = page.locator(".site-header");
    await expect(header).toHaveCSS("position", "sticky");
    await page.evaluate(() => window.scrollTo(0, 1200));
    await expect
      .poll(() => header.evaluate((element) => element.getBoundingClientRect().top))
      .toBe(0);

    const flyerCards = page.locator(".flyer-card");
    await expect(flyerCards).toHaveCount(4);
    for (const image of await flyerCards.locator("img").all()) {
      await image.scrollIntoViewIfNeeded();
      await expect.poll(() => image.evaluate((item) => item.naturalWidth)).toBeGreaterThan(0);
    }

    await page.evaluate(() => window.dispatchEvent(new Event("scroll")));
    await expect(page.locator("script#angel-tawk-chat")).toHaveCount(1);

    await page.goto("http://127.0.0.1:8089/flyers/", {
      waitUntil: "domcontentloaded"
    });
    await expect(page.locator("h1")).toHaveText(
      "Current granite monument flyers."
    );
    await expect(page.locator(".flyer-card")).toHaveCount(4);
    const pdfLinks = page.locator('.flyer-card[href$=".pdf"]');
    await expect(pdfLinks).toHaveCount(4);
    for (const link of await pdfLinks.all()) {
      const href = await link.getAttribute("href");
      const response = await page.request.get(new URL(href, page.url()).href);
      expect(response.ok()).toBeTruthy();
      expect(response.headers()["content-type"]).toContain("application/pdf");
    }
  });

  test("modern app launcher follows the visitor device", async ({
    browser
  }) => {
    const deviceCases = [
      {
        userAgent:
          "Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148",
        shown: "Download Angel Granites on the App Store",
        hidden: "Get Angel Granites on Google Play",
        href: "https://apps.apple.com/us/app/angel-granties/id6748974666"
      },
      {
        userAgent:
          "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 Chrome/131.0 Mobile Safari/537.36",
        shown: "Get Angel Granites on Google Play",
        hidden: [
          "Download Angel Granites on the App Store",
          "Get Angel Granites from the Microsoft Store"
        ],
        href: "https://play.google.com/store/apps/details?id=com.angelgranites.app"
      },
      {
        userAgent:
          "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/131.0 Safari/537.36",
        shown: "Get Angel Granites from the Microsoft Store",
        hidden: [
          "Download Angel Granites on the App Store",
          "Get Angel Granites on Google Play"
        ],
        href:
          "https://apps.microsoft.com/detail/9NPQBXKDHPML?hl=en-us&gl=US&ocid=pdpshare"
      }
    ];

    for (const device of deviceCases) {
      const context = await browser.newContext({
        userAgent: device.userAgent,
        viewport: { width: 390, height: 844 }
      });
      const page = await context.newPage();
      await page.route("https://embed.tawk.to/**", (route) => route.abort());
      await page.goto("http://127.0.0.1:8089/", {
        waitUntil: "domcontentloaded"
      });
      const appLink = page.getByRole("link", { name: device.shown });
      await expect(appLink).toBeVisible();
      await expect(appLink).toHaveAttribute("href", device.href);
      for (const hiddenLabel of Array.isArray(device.hidden)
        ? device.hidden
        : [device.hidden]) {
        await expect(
          page.getByRole("link", { name: hiddenLabel })
        ).toHaveCount(0);
      }
      await context.close();
    }

    const desktop = await browser.newContext({
      viewport: { width: 1440, height: 900 }
    });
    const page = await desktop.newPage();
    await page.route("https://embed.tawk.to/**", (route) => route.abort());
    await page.goto("http://127.0.0.1:8089/", {
      waitUntil: "domcontentloaded"
    });
    const launcher = page.getByRole("button", { name: "Get the app" });
    await expect(launcher).toHaveAttribute("aria-expanded", "false");
    await launcher.click();
    await expect(launcher).toHaveAttribute("aria-expanded", "true");
    await expect(page.getByRole("link", { name: /iPhone & iPad/ })).toBeVisible();
    await expect(page.getByRole("link", { name: /Android/ })).toBeVisible();
    await expect(page.getByRole("link", { name: /Windows/ })).toHaveAttribute(
      "href",
      "https://apps.microsoft.com/detail/9NPQBXKDHPML?hl=en-us&gl=US&ocid=pdpshare"
    );
    await page.keyboard.press("Escape");
    await expect(launcher).toHaveAttribute("aria-expanded", "false");
    await desktop.close();
  });

  test("theme preference supports system, dark and light modes", async ({
    page
  }) => {
    await page.emulateMedia({ colorScheme: "light" });
    await page.goto("http://127.0.0.1:8089/", { waitUntil: "domcontentloaded" });
    const selector = page.getByLabel("Color theme");
    await expect(selector.locator("..").locator("svg")).toBeVisible();
    await expect(selector).toHaveValue("dark");
    await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
    const themeControlBox = await selector.locator("..").boundingBox();
    expect(themeControlBox.width).toBeLessThanOrEqual(44);
    expect(themeControlBox.height).toBeLessThanOrEqual(44);
    await selector.selectOption("system");
    await expect(page.locator("html")).toHaveAttribute("data-theme", "light");

    await selector.selectOption("dark");
    await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
    await page.reload({ waitUntil: "domcontentloaded" });
    await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
    await expect(page.getByLabel("Color theme")).toHaveValue("dark");

    await page.getByLabel("Color theme").selectOption("light");
    await expect(page.locator("html")).toHaveAttribute("data-theme", "light");
    await expect(page.locator("body")).toHaveCSS(
      "background-color",
      "rgb(245, 242, 235)"
    );
  });

  test("SEO metadata, structured data and the existing GA4 measurement are preserved", async ({
    page
  }) => {
    await page.route("https://www.googletagmanager.com/**", (route) =>
      route.abort()
    );
    await page.route("**/inventory-proxy.php?**", async (route) => {
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({ success: true, data: [] })
      });
    });
    const routes = [
      "/",
      "/inventory/",
      "/monuments/",
      "/colors/bahama-blue-granite/",
      "/designs/ag-396-carved-angel-heart-monument/",
      "/resources/monument-glossary/",
      "/locations/barre-vt/"
    ];

    for (const route of routes) {
      await page.goto(`http://127.0.0.1:8089${route}`, {
        waitUntil: "domcontentloaded"
      });
      const canonical = await page
        .locator('link[rel="canonical"]')
        .getAttribute("href");
      expect(await page.title()).not.toBe("");
      await expect(page.locator('meta[name="description"]')).toHaveAttribute(
        "content",
        /.+/
      );
      await expect(page.locator('meta[property="og:url"]')).toHaveAttribute(
        "content",
        canonical
      );
      await expect(page.locator('meta[property="og:image"]')).toHaveAttribute(
        "content",
        /^https:\/\/www\.theangelstones\.com\//
      );
      await expect(page.locator("h1")).toHaveCount(1);
      await expect(
        page.locator('script[type="application/ld+json"]')
      ).not.toHaveCount(0);
      if (route.startsWith("/colors/") || route.startsWith("/designs/")) {
        const structuredData = (
          await page
            .locator('script[type="application/ld+json"]')
            .allTextContents()
        ).map((value) => JSON.parse(value));
        expect(
          structuredData.some((item) => item["@type"] === "ItemPage")
        ).toBe(true);
        expect(
          structuredData.some((item) => item["@type"] === "Product")
        ).toBe(false);
      }
    }

    await expect(
      page.locator('script[src*="gtag/js?id=G-Y5TBVH7CY7"]')
    ).toHaveCount(1);
    await expect(page.locator('script[src*="gtm.js"]')).toHaveCount(0);

    const sitemap = await (
      await page.request.get("http://127.0.0.1:8089/sitemap.xml")
    ).text();
    expect(sitemap).not.toContain("/discovered.html");
    expect(sitemap).toContain("/designs/ag-396-carved-angel-heart-monument/");
    expect(
      (sitemap.match(/<loc>https:\/\/www\.theangelstones\.com\/colors\//g) || [])
        .length
    ).toBe(63);
  });

  test("agent guidance is valid Markdown with canonical catalog links", async ({
    page
  }) => {
    const llmsResponse = await page.request.get(
      "http://127.0.0.1:8089/llms.txt"
    );
    expect(llmsResponse.status()).toBe(200);
    expect(llmsResponse.headers()["content-type"]).toContain("text/plain");
    const llms = await llmsResponse.text();
    expect(llms).toMatch(/^# Angel Granites/m);
    expect(llms).toContain(
      "[Current inventory](https://www.theangelstones.com/inventory/)"
    );
    expect(llms).toContain(
      "[Granite colors](https://www.theangelstones.com/granite-colors/)"
    );
    expect(llms).not.toMatch(/<(?:html|head|body)\b/i);
  });
});
