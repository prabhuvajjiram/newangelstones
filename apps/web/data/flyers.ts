import fs from "node:fs";
import path from "node:path";

type FlyerSource = {
  id: string;
  name: string;
  description: string;
  image: string;
  pdf: string;
  label: string;
};

export type Flyer = FlyerSource & {
  imagePath: string;
  pdfPath: string;
};

const repositoryRoot = path.resolve(process.cwd(), "../..");
const featuredFlyerOrder = [
  "Blue_Special_Flyer",
  "Convention_special_Flyer",
  "Green_Special_Flyer",
  "Special Designs Flyer_4"
];

function flyerOrder(id: string) {
  const index = featuredFlyerOrder.indexOf(id);
  return index === -1 ? Number.MAX_SAFE_INTEGER : index;
}

function localPath(url: string) {
  return decodeURIComponent(new URL(url).pathname);
}

export const flyers: Flyer[] = (
  JSON.parse(
    fs.readFileSync(
      path.join(repositoryRoot, "mobile_app/assets/specials.json"),
      "utf8"
    )
  ) as FlyerSource[]
)
  .map((flyer) => {
    const imagePath = localPath(flyer.image);
    const pdfPath = localPath(flyer.pdf);
    for (const asset of [imagePath, pdfPath]) {
      if (!fs.existsSync(path.join(repositoryRoot, asset.replace(/^\/+/, "")))) {
        throw new Error(`Flyer asset is missing: ${flyer.name} -> ${asset}`);
      }
    }
    return { ...flyer, imagePath, pdfPath };
  })
  .sort((left, right) => flyerOrder(left.id) - flyerOrder(right.id));
