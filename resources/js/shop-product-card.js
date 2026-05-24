function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function updateCompareBadge(count) {
    document.querySelectorAll('[data-compare-count]').forEach((el) => {
        el.textContent = count > 99 ? '99+' : String(count);
        el.classList.toggle('hb-cart-hidden', count < 1);
    });
}

function updateWishlistBadge(count) {
    document.querySelectorAll('[data-wishlist-count]').forEach((el) => {
        el.textContent = count > 99 ? '99+' : String(count);
        el.classList.toggle('hidden', count < 1);
        el.classList.toggle('hb-cart-hidden', count < 1);
    });

    const iconFill = document.querySelector('.hb-wishlist-icon-wrap svg');
    if (iconFill) {
        iconFill.setAttribute('fill', count > 0 ? 'currentColor' : 'none');
    }
}

document.addEventListener('click', async (e) => {
    const compareBtn = e.target.closest('[data-product-compare]');
    if (compareBtn) {
        e.preventDefault();
        e.stopPropagation();

        const loginUrl = compareBtn.dataset.loginUrl;
        const compareUrl = compareBtn.dataset.compareUrl;

        if (!compareUrl) {
            window.location.href = loginUrl || '/login';
            return;
        }

        compareBtn.classList.add('is-loading');

        try {
            const res = await fetch(compareUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (res.status === 401 || res.redirected) {
                window.location.href = loginUrl || '/login';
                return;
            }

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                alert(data.message || 'تعذّرت إضافة المنتج للمقارنة');
                return;
            }

            const added = Boolean(data.added);
            const productId = compareBtn.dataset.productId;
            document.querySelectorAll(`[data-product-compare][data-product-id="${productId}"]`).forEach((btn) => {
                btn.classList.toggle('is-active', added);
                const label = btn.querySelector('span');
                if (label && btn.dataset.addLabel) {
                    label.textContent = added ? btn.dataset.removeLabel : btn.dataset.addLabel;
                }
            });

            if (typeof data.count === 'number') {
                updateCompareBadge(data.count);
                window.dispatchEvent(new CustomEvent('compare:updated', { detail: { count: data.count } }));
            }
        } catch {
            //
        } finally {
            document.querySelectorAll(`[data-product-compare][data-product-id="${compareBtn.dataset.productId}"]`).forEach((btn) => {
                btn.classList.remove('is-loading');
            });
        }

        return;
    }

    const wishlistBtn = e.target.closest('[data-product-wishlist]');
    if (wishlistBtn) {
        e.preventDefault();
        e.stopPropagation();

        const loginUrl = wishlistBtn.dataset.loginUrl;
        const favoriteUrl = wishlistBtn.dataset.favoriteUrl;

        if (!favoriteUrl) {
            window.location.href = loginUrl || '/login';
            return;
        }

        document.querySelectorAll(`[data-product-wishlist][data-product-id="${wishlistBtn.dataset.productId}"]`).forEach((btn) => {
            btn.classList.add('is-loading');
        });

        try {
            const res = await fetch(favoriteUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (res.status === 401 || res.redirected) {
                window.location.href = loginUrl || '/login';
                return;
            }

            const data = await res.json();
            const added = Boolean(data.added);
            const productId = wishlistBtn.dataset.productId;
            document.querySelectorAll(`[data-product-wishlist][data-product-id="${productId}"]`).forEach((btn) => {
                btn.classList.toggle('is-active', added);
                const icon = btn.querySelector('svg');
                if (icon) {
                    icon.setAttribute('fill', added ? 'currentColor' : 'none');
                }
                const label = btn.querySelector('span');
                if (label && btn.dataset.addLabel) {
                    label.textContent = added ? btn.dataset.removeLabel : btn.dataset.addLabel;
                }
            });

            if (typeof data.count === 'number') {
                updateWishlistBadge(data.count);
                window.dispatchEvent(new CustomEvent('wishlist:updated', { detail: { count: data.count } }));
            }

            if (typeof window.refreshMiniWishlist === 'function') {
                window.refreshMiniWishlist();
            }
        } catch {
            //
        } finally {
            document.querySelectorAll(`[data-product-wishlist][data-product-id="${wishlistBtn.dataset.productId}"]`).forEach((btn) => {
                btn.classList.remove('is-loading');
            });
        }

        return;
    }

    const cartBtn = e.target.closest('[data-product-add-cart]');
    if (!cartBtn) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const apiBase = cartBtn.dataset.api;
    const sessionId = cartBtn.dataset.sessionId;
    const productId = parseInt(cartBtn.dataset.productId, 10);
    const token = localStorage.getItem('api_token');
    const originalHtml = cartBtn.innerHTML;

    cartBtn.classList.add('is-loading');
    cartBtn.disabled = true;

    try {
        const res = await fetch(`${apiBase}/cart/items`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Session-Id': sessionId,
                ...(token ? { Authorization: `Bearer ${token}` } : {}),
            },
            body: JSON.stringify({ product_id: productId, quantity: 1 }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'تعذّرت الإضافة إلى السلة');
            return;
        }

        const data = await res.json();
        const count = data.totals?.items_count ?? 0;

        cartBtn.classList.add('is-added');
        cartBtn.innerHTML = '✓ ' + (cartBtn.dataset.addedLabel || 'تمت الإضافة');

        window.dispatchEvent(new CustomEvent('cart:updated', { detail: { count } }));

        if (typeof window.refreshMiniCart === 'function') {
            window.refreshMiniCart();
        }
    } catch {
        alert('تعذّرت الإضافة إلى السلة');
    } finally {
        cartBtn.classList.remove('is-loading');
        cartBtn.disabled = false;

        setTimeout(() => {
            cartBtn.classList.remove('is-added');
            cartBtn.innerHTML = originalHtml;
        }, 2000);
    }
});
