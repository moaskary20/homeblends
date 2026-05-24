(function () {
    const root = document.querySelector('.hb-points-page');
    if (!root) {
        return;
    }

    const pointValue = parseFloat(root.dataset.pointValue || '0');
    const minRedeem = parseInt(root.dataset.minRedeem || '0', 10);
    const maxRedeem = parseInt(root.dataset.maxRedeem || '0', 10);
    const input = root.querySelector('[data-redeem-points-input]');
    const preview = root.querySelector('[data-redeem-preview-amount]');
    const maxBtn = root.querySelector('[data-redeem-max]');

    function updatePreview() {
        if (!input || !preview) {
            return;
        }
        const points = Math.min(maxRedeem, Math.max(0, parseInt(input.value || '0', 10)));
        preview.textContent = (points * pointValue).toFixed(2);
    }

    if (input) {
        input.addEventListener('input', updatePreview);
        updatePreview();
    }

    if (maxBtn && input) {
        maxBtn.addEventListener('click', () => {
            input.value = String(maxRedeem);
            updatePreview();
        });
    }
})();
