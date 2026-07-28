const { test, expect } = require("@playwright/test");

const stagingBaseUrl =
  process.env.STAGING_BASE_URL || "http://127.0.0.1:8091/new";

test.describe("cPanel /new staging package", () => {
  test.skip(
    !process.env.STAGING_BASE_URL,
    "Set STAGING_BASE_URL when testing an extracted /new package."
  );

  test("base-path assets, live colors and inventory APIs resolve correctly", async ({
    page
  }) => {
    const failedLocalResponses = [];
    const apiRequests = [];
    page.on("response", (response) => {
      const url = new URL(response.url());
      if (
        url.hostname === "127.0.0.1" &&
        response.status() >= 400 &&
        !url.pathname.endsWith("/favicon.ico")
      ) {
        failedLocalResponses.push(`${response.status()} ${url.pathname}`);
      }
    });

    await page.route("**/get_color_images.php", async (route) => {
      apiRequests.push(new URL(route.request().url()).pathname);
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          colors: [
            {
              name: "Test Gold",
              path: "images/colors/Rustic%20Brown.webp"
            }
          ]
        })
      });
    });
    await page.route("**/get_directory_files.php?**", async (route) => {
      apiRequests.push(new URL(route.request().url()).pathname);
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({ success: true, files: [] })
      });
    });
    await page.route("**/new/inventory-proxy.php?**", async (route) => {
      apiRequests.push(new URL(route.request().url()).pathname);
      await route.fulfill({
        contentType: "application/json",
        body: JSON.stringify({
          success: true,
          data: [
            {
              c: "STAGE-1",
              d: "Staging granite tablet",
              t: "Tablet",
              g: "Bahama Blue",
              n: "AG-298",
              f: "P5",
              s: "3-0 X 0-8 X 2-4",
              l: "Elberton",
              q: "1"
            }
          ]
        })
      });
    });

    await page.goto(`${stagingBaseUrl}/granite-colors/`, {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".color-card")).toHaveCount(58);
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute(
      "href",
      "https://www.theangelstones.com/granite-colors/"
    );
    await expect(page.locator(".color-card").first()).toHaveAttribute(
      "href",
      /^\/new\/colors\//
    );
    await expect(page.locator(".color-card img").first()).toHaveAttribute(
      "src",
      /^\/new\/images\/colors\//
    );
    await expect(
      page.getByRole("link", { name: /Test Gold Granite/ })
    ).toHaveAttribute("href", /^\/new\/inventory\/\?search=/);

    await page.goto(`${stagingBaseUrl}/inventory/`, {
      waitUntil: "networkidle"
    });
    await expect(page.locator(".inventory-result")).toHaveCount(1);
    expect(apiRequests).toContain("/get_color_images.php");
    expect(apiRequests).toContain("/get_directory_files.php");
    expect(apiRequests).toContain("/new/inventory-proxy.php");
    expect(failedLocalResponses).toEqual([]);
  });
});
