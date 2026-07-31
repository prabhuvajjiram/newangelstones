const { test, expect } = require("@playwright/test");
const AxeBuilder = require("@axe-core/playwright").default;

const routes = [
  "/",
  "/inventory/",
  "/benches/",
  "/granite-colors/",
  "/colors/aurora-granite/",
  "/designs/ag-396-carved-angel-heart-monument/",
  "/products-services/",
  "/flyers/",
  "/resources/",
  "/resources/how-to-read-monument-dimensions/",
  "/resources/granite-monument-ordering-checklist/",
  "/locations/barre-vt/",
  "/contact/",
  "/privacy-policy/",
  "/terms-of-service/",
  "/sms-terms/"
];

async function scan(page, label) {
  const results = await new AxeBuilder({ page })
    .withTags(["wcag2a", "wcag2aa", "wcag21a", "wcag21aa", "wcag22aa"])
    .analyze();
  expect(
    results.violations,
    `${label}\n${results.violations
      .map(
        (violation) =>
          `${violation.id}: ${violation.help}\n${violation.nodes
            .map((node) => `  ${node.target.join(" ")}: ${node.failureSummary}`)
            .join("\n")}`
      )
      .join("\n")}`
  ).toEqual([]);
}

for (const theme of ["dark", "light"]) {
  test(`public routes pass WCAG A and AA checks in ${theme} theme`, async ({
    page
  }) => {
    await page.addInitScript((preference) => {
      window.localStorage.setItem("angel-theme", preference);
    }, theme);
    await page.route("https://embed.tawk.to/**", (route) => route.abort());

    for (const route of routes) {
      await page.goto(`http://127.0.0.1:8089${route}`, {
        waitUntil: "domcontentloaded"
      });
      if (route === "/inventory/") {
        await page.locator(".inventory-result").first().waitFor({
          timeout: 30000
        });
      }
      await scan(page, `${theme} ${route}`);
    }
  });
}

test("inventory dialog is keyboard operable and accessible", async ({ page }) => {
  await page.addInitScript(() => {
    window.localStorage.setItem("angel-theme", "dark");
  });
  await page.goto("http://127.0.0.1:8089/inventory/", {
    waitUntil: "domcontentloaded"
  });
  const firstItem = page.locator(".inventory-result").first();
  await firstItem.waitFor({ timeout: 30000 });
  await firstItem.focus();
  await page.keyboard.press("Enter");
  const dialog = page.getByRole("dialog");
  await expect(dialog).toBeVisible();
  await expect(page.getByRole("button", { name: "Close inventory details" })).toBeFocused();
  await scan(page, "inventory detail dialog");
  await page.keyboard.press("Escape");
  await expect(dialog).toHaveCount(0);
  await expect(firstItem).toBeFocused();
});

test("collection image lightbox is keyboard operable and accessible", async ({
  page
}) => {
  await page.addInitScript(() => {
    window.localStorage.setItem("angel-theme", "dark");
  });
  await page.goto("http://127.0.0.1:8089/benches/", {
    waitUntil: "networkidle"
  });

  const trigger = page.getByRole("button", {
    name: /^View .* larger$/
  }).first();
  await trigger.click();
  const dialog = page.getByRole("dialog");
  await expect(dialog).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Close image preview" })
  ).toBeFocused();
  await scan(page, "collection image lightbox");

  const initialLabel = await dialog.getAttribute("aria-label");
  await page.keyboard.press("ArrowRight");
  await expect(dialog).not.toHaveAttribute("aria-label", initialLabel);
  await page.keyboard.press("Escape");
  await expect(dialog).toHaveCount(0);
  await expect(trigger).toBeFocused();
});
