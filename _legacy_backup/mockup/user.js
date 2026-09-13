const mockUsers = [];

const firstNames = [
    "สมชาย",
    "สมหญิง",
    "กิตติ",
    "อนันต์",
    "ธนภัทร",
    "ณัฐพล",
    "พิมพ์ชนก",
    "สุภาวดี",
    "ชลธิชา",
    "นภัสสร",
];

const lastNames = [
    "ใจดี",
    "สุขใจ",
    "วงศ์ดี",
    "ศรีสุข",
    "ทองดี",
    "เจริญสุข",
    "บุญมี",
    "แสงทอง",
    "คำดี",
    "วัฒนา",
];

const userRoles = ["user", "admin"];
const userStatuses = ["active", "inactive"];

for (let i = 1; i <= 100; i++) {
    const firstName =
        firstNames[Math.floor(Math.random() * firstNames.length)];

    const lastName =
        lastNames[Math.floor(Math.random() * lastNames.length)];

    mockUsers.push({
        id: i,
        img: `./assets/profile-mock.png`,
        username: `kkc${i}`,
        name: `${firstName} ${lastName}`,
        email: `kkc${i}@g.co`,
        phone: `08${Math.floor(10000000 + Math.random() * 90000000)}`,
        role: i <= 5 ? "admin" : "user",
        status:
            userStatuses[Math.floor(Math.random() * userStatuses.length)],
    });
}

console.log(mockUsers);
console.log("=======================================");