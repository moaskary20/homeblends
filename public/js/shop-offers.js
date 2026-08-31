(function () {
    const buy = document.querySelector('[data-offer-buy]');
    const button = buy?.querySelector('[data-offer-add]');
    if (!buy || !button) {
        return;
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    button.addEventListener('click', async () => {
        if (button.disabled) {
            return;
        }

        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.classList.add('is-loading');

        try {
            const token = typeof window.shopAuthToken === 'function' ? window.shopAuthToken() : null;
            const headers = typeof window.shopGuestFetchHeaders === 'function'
                ? window.shopGuestFetchHeaders({ 'Content-Type': 'application/json' })
                : {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                };

            const selected = buy.querySelector('input[name="installment_months"]:checked');
            const months = selected ? Number(selected.value) : null;

            const res = await fetch(buy.dataset.offerCartUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    ...headers,
                    ...(token ? { Authorization: `Bearer ${token}` } : {}),
                },
                body: JSON.stringify(months ? { installment_months: months } : {}),
            });

            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const message = data.message
                    || Object.values(data.errors || {}).flat()[0]
                    || buy.dataset.errorLabel
                    || 'تعذّرت الإضافة إلى السلة';
                throw new Error(message);
            }

            const count = data.totals?.items_count ?? 0;
            button.classList.add('is-added');
            button.textContent = '✓ ' + (buy.dataset.addedLabel || 'تمت الإضافة');

            window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count } }));
            if (typeof window.scheduleMiniCartRefresh === 'function') {
                window.scheduleMiniCartRefresh(count);
            } else if (typeof window.refreshMiniCart === 'function') {
                window.refreshMiniCart(count);
            }
        } catch (error) {
            alert(error.message || buy.dataset.errorLabel || 'تعذّرت الإضافة إلى السلة');
        } finally {
            button.classList.remove('is-loading');
            button.disabled = false;
            setTimeout(() => {
                button.classList.remove('is-added');
                button.innerHTML = originalHtml;
            }, 2000);
        }
    });
})();
