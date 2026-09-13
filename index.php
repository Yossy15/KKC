<?php
/**
 * Home Page (KKC)
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/data/products_data.php';

$page_title = 'KKC - Home';
include __DIR__ . '/includes/header.php';

$sections = [
    [
        'title' => 'ALL PRODUCTS',
        'types' => ['shirt', 'pants', 'skirt', 'cap'],
        'query' => 'product[]=shirt&product[]=pants&product[]=skirt&product[]=cap',
    ],
    [
        'title' => 'T-SHIRT',
        'types' => ['shirt'],
        'query' => 'product[]=shirt',
    ],
    [
        'title' => 'PANTS',
        'types' => ['pants'],
        'query' => 'product[]=pants',
    ],
    [
        'title' => 'SKIRTS',
        'types' => ['skirt'],
        'query' => 'product[]=skirt',
    ],
    [
        'title' => 'CAPS',
        'types' => ['cap'],
        'query' => 'product[]=cap',
    ],
];
?>

<section>
    <main>
        <?php foreach ($sections as $sec): 
            $filtered = filter_products([
                'product' => $sec['types'],
                'status'  => ['พร้อมส่ง'],
            ]);
            $items = array_slice($filtered, 0, 10);
        ?>
            <section>
                <div class="merch-container">
                    <h1 class="title"><?= e($sec['title']) ?></h1>
                    <a class="arrow-btn" href="<?= base_url('products.php?' . $sec['query']) ?>" aria-label="เพิ่มเติม">
                        <span class="arrow-btn-text">เพิ่มเติม</span>
                        <span class="arrow-btn-icon">&#8250;</span>
                    </a>
                </div>

                <div class="merch-scroll-wrapper">
                    <button class="merch-scroll-btn merch-scroll-btn--left" aria-label="เลื่อนซ้าย">&#8249;</button>
                    <div class="merch-container-body">
                        <?php foreach ($items as $product): ?>
                            <?php include __DIR__ . '/includes/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                    <button class="merch-scroll-btn merch-scroll-btn--right" aria-label="เลื่อนขวา">&#8250;</button>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
</section>

<script>
    // Horizontal carousel scroll handler
    document.querySelectorAll(".merch-scroll-wrapper").forEach((w) => {
        const track = w.querySelector(".merch-container-body");
        const btnL = w.querySelector(".merch-scroll-btn--left");
        const btnR = w.querySelector(".merch-scroll-btn--right");

        const update = () => {
            const max = track.scrollWidth - track.clientWidth;
            btnL.style.display = track.scrollLeft <= 1 ? "none" : "flex";
            btnR.style.display = track.scrollLeft >= max - 1 ? "none" : "flex";
        };

        btnL.onclick = () => {
            const card = track.querySelector(".merch-container-card");
            const gap = parseFloat(getComputedStyle(track).gap) || 0;
            track.scrollBy({
                left: -((card ? card.offsetWidth : 200) + gap),
                behavior: "smooth",
            });
        };

        btnR.onclick = () => {
            const card = track.querySelector(".merch-container-card");
            const gap = parseFloat(getComputedStyle(track).gap) || 0;
            track.scrollBy({
                left: ((card ? card.offsetWidth : 200) + gap),
                behavior: "smooth",
            });
        };

        track.onscroll = update;
        window.addEventListener("resize", update);
        update();
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
