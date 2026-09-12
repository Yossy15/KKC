const mockProducts = [];

const productTypes = [
  { type: "shirt", image: "./assets/shirt.png", name: "Classic Tee" },
  { type: "pants", image: "./assets/pants.png", name: "Classic Pants" },
  { type: "skirt", image: "./assets/skirt.png", name: "Classic Skirt" },
  { type: "cap", image: "./assets/cap.png", name: "Classic Cap" },
];

const sizes = ["S", "M", "L"];
const prices = [20, 25, 30, 35];
const colors = ["ดำ", "ขาว", "เทา", "น้ำเงิน", "แดง"];

for (let i = 1; i <= 100; i++) {
  const productType = productTypes[i % productTypes.length];

  const randomSize = sizes[Math.floor(Math.random() * sizes.length)];
  const randomPrice = prices[Math.floor(Math.random() * prices.length)];
  const randomColor = colors[Math.floor(Math.random() * colors.length)];

  mockProducts.push({
    id: i,
    type: productType.type,
    image: productType.image,
    name: `${productType.name} #${i}`,
    size: randomSize,
    price: randomPrice,
    color: randomColor,
  });
}