function initMerchScroll(wrapper) {
    const track = wrapper.querySelector('.merch-container-body');
    const btnLeft = wrapper.querySelector('.merch-scroll-btn--left');
    const btnRight = wrapper.querySelector('.merch-scroll-btn--right');

    function updateButtons() {
        const maxScroll = track.scrollWidth - track.clientWidth;
        const atStart = track.scrollLeft <= 1;
        const atEnd = track.scrollLeft >= maxScroll - 1;

        btnLeft.style.display = atStart ? 'none' : 'flex';
        btnRight.style.display = atEnd ? 'none' : 'flex';
    }

    btnLeft.addEventListener('click', () => {
        const card = track.querySelector('.merch-container-card');
        const gap = parseFloat(getComputedStyle(track).gap) || 0;
        track.scrollBy({ left: -(card.offsetWidth + gap), behavior: 'smooth' });
    });

    btnRight.addEventListener('click', () => {
        const card = track.querySelector('.merch-container-card');
        const gap = parseFloat(getComputedStyle(track).gap) || 0;
        track.scrollBy({ left: card.offsetWidth + gap, behavior: 'smooth' });
    });

    track.addEventListener('scroll', updateButtons);
    window.addEventListener('resize', updateButtons);

    updateButtons();
}

document.querySelectorAll('.merch-scroll-wrapper').forEach(initMerchScroll);