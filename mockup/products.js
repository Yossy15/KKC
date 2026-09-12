const mockProducts = (() => {
    const products = [];

    const productTypes = [
        { type: "shirt", image: "./assets/shirt.png", name: "Classic Tee" },
        { type: "pants", image: "./assets/pants.png", name: "Classic Pants" },
        { type: "skirt", image: "./assets/skirt.png", name: "Classic Skirt" },
        { type: "cap", image: "./assets/cap.png", name: "Classic Cap" }
    ];

    const sizes = ["S", "M", "L"];
    const prices = [20, 25, 30, 35];
    const colors = ["ดำ", "ขาว", "เทา", "น้ำเงิน", "แดง"];

    for (let i = 1; i <= 500; i++) {
        const productType = productTypes[i % productTypes.length];
        const size = sizes[i % sizes.length];
        const price = prices[i % prices.length];
        const color = colors[i % colors.length];

        products.push({
            id: i,
            type: productType.type,
            image: productType.image,
            name: `${productType.name} #${i}`,
            size: size,
            price: price,
            color: color
        });
    }

    return products;
})();

// export 
if (typeof module !== 'undefined' && module.exports) {
    module.exports = mockProducts;
}
