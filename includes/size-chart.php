<?php
/**
 * Shared Size Chart Modal Component
 */
?>
<section>
    <button class="size-chart" command="show-modal" commandfor="my-dialog" type="button" onclick="document.getElementById('my-dialog').showModal()">
        ตารางไซส์
    </button>

    <dialog id="my-dialog">
        <button commandfor="my-dialog" command="close" type="button" onclick="document.getElementById('my-dialog').close()">
            <img src="<?= asset_url('public/close.svg') ?>" alt="close" />
        </button>
        <img src="<?= asset_url('assets/size-chart.png') ?>" alt="size-chart" class="size-chart-img" />
    </dialog>
</section>
