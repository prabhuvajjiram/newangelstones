import fs from "node:fs";
import path from "node:path";

export type InventoryProductImage = {
  code: string;
  name: string;
  path: string;
  thumbnailPath: string;
  category: string;
  isArchive: boolean;
};

const repositoryRoot = path.resolve(process.cwd(), "../..");
const productRoot = path.join(repositoryRoot, "images/products");
const supportedImage = /\.(?:avif|gif|jpe?g|png|webp)$/i;
const designCode = /(?:AG|AS|DF)-?\d+[A-Z]?(?:-\d+)?/i;

function walk(directory: string): string[] {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    if (entry.name.startsWith(".")) return [];
    const absolute = path.join(directory, entry.name);
    return entry.isDirectory() ? walk(absolute) : [absolute];
  });
}

export const inventoryImageIndex: InventoryProductImage[] = walk(productRoot)
  .filter((file) => supportedImage.test(file))
  .flatMap((file) => {
    const relative = path.relative(productRoot, file);
    const match = path.basename(file).match(designCode);
    if (!match) return [];
    return [
      {
        code: match[0].toUpperCase(),
        name: path.basename(file, path.extname(file)),
        path: `/images/products/${relative.split(path.sep).join("/")}`,
        thumbnailPath: `/images/inventory-thumbnails/${relative
          .replace(path.extname(relative), ".webp")
          .split(path.sep)
          .join("/")}`,
        category: relative.split(path.sep)[0],
        isArchive: relative.split(path.sep).includes("Old Photos")
      }
    ];
  })
  .sort(
    (left, right) =>
      Number(left.isArchive) - Number(right.isArchive) ||
      left.path.localeCompare(right.path)
  );
