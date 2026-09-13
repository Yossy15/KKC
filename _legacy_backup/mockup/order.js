const mockOrders = [];

const orderStatuses = [
    "รอดำเนินการ",
    "กำลังจัดส่ง",
    "จัดส่งแล้ว",
    "ยกเลิก",
];

for (let i = 1; i <= 200; i++) {
    const userId =
        Math.floor(Math.random() * mockUsers.length) + 1;

    const productId =
        Math.floor(Math.random() * mockProducts.length) + 1;

    const product = mockProducts.find(
        (item) => item.id === productId
    );

    const quantity = Math.floor(Math.random() * 3) + 1;

    mockOrders.push({
        id: i,

        // Relation → User
        userId: userId,

        // Relation → Product
        productId: productId,

        quantity: quantity,

        price: product.price,

        total: product.price * quantity,

        status:
            orderStatuses[
            Math.floor(Math.random() * orderStatuses.length)
            ],

        createdAt: new Date(
            Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000
        ).toISOString(),
    });
}

console.log(mockOrders);
console.log("=======================================");