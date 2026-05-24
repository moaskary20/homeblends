function wishlistCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeWishlistHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

function updateWishlistCountBadge(count) {
    document.querySelectorAll('[data-wishlist-count]').forEach((el) => {
        el.textContent = count > 99 ? '99+' : String(count);
        el.classList.toggle('hb-cart-hidden', count < 1);
    });

    const iconFill = document.querySelector('.hb-wishlist-icon-wrap svg');
    if (iconFill) {
        iconFill.setAttribute('fill', count > 0 ? 'currentColor' : 'none');
    }
}

function renderMiniWishlistItem(item, root) {
    const currency = root.dataset.currency || '';
    const removeLabel = escapeWishlistHtml(root.dataset.removeLabel || '');

    return `
        <li class="hb-mini-wishlist-item" data-product-id="${item.id}">
            <a href="${item.url}" class="hb-mini-wishlist-item-thumb">
                ${item.thumb
                    ? `<img src="${item.thumb}" alt="${escapeWishlistHtml(item.name)}" loading="lazy" width="48" height="48">`
                    : '<span class="hb-mini-wishlist-item-placeholder">❤️</span>'}
            </a>
            <div class="hb-mini-wishlist-item-info">
                <a href="${item.url}" class="hb-mini-wishlist-item-title">${escapeWishlistHtml(item.name)}</a>
                <span class="hb-mini-wishlist-item-meta">${Number(item.price).toFixed(2)} ${currency}</span>
            </div>
            <button type="button"
                    class="hb-mini-wishlist-remove"
                    data-wishlist-remove
                    data-remove-url="${item.remove_url}"
                    title="${removeLabel}"
                    aria-label="${removeLabel}">✕</button>
        </li>
    `;
}

async function refreshMiniWishlist() {
    const root = document.querySelector('[data-mini-wishlist]');
    const body = root?.querySelector('[data-mini-wishlist-body]');
    const previewUrl = root?.dataset.previewUrl;

    if (!root || !body || !previewUrl) {
        return;
    }

    try {
        const res = await fetch(previewUrl, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            return;
        }

        const data = await res.json();
        const count = data.count ?? 0;
        const items = data.items ?? [];

        updateWishlistCountBadge(count);

        const countEl = root.querySelector('[data-mini-wishlist-count]');
        if (countEl) {
            countEl.textContent = count > 0 ? `${count} ${root.dataset.itemsLabel || ''}`.trim() : '';
            countEl.classList.toggle('hb-cart-hidden', count < 1);
        }

        if (count < 1) {
            body.innerHTML = `
                <div class="hb-mini-wishlist-empty">
                    <p>${escapeWishlistHtml(root.dataset.emptyText)}</p>
                    <a href="${root.dataset.continueUrl}" class="hb-mini-wishlist-link">${escapeWishlistHtml(root.dataset.continueLabel)}</a>
                </div>
            `;
            return;
        }

        const moreCount = Math.max(0, count - items.length);
        const moreHtml = data.has_more && moreCount > 0
            ? `<p class="hb-mini-wishlist-more text-xs text-gray-500 px-4 py-2 border-t border-gray-100" data-mini-wishlist-more>${escapeWishlistHtml((root.dataset.moreTemplate || '').replace(':count', String(moreCount)))}</p>`
            : '';

        body.innerHTML = `
            <ul class="hb-mini-wishlist-items">${items.map((item) => renderMiniWishlistItem(item, root)).join('')}</ul>
            ${moreHtml}
            <div class="hb-mini-wishlist-footer">
                <a href="${root.dataset.favoritesUrl}" class="hb-mini-wishlist-btn">${escapeWishlistHtml(root.dataset.viewLabel)}</a>
            </div>
        `;
    } catch {
        //
    }
}

window.refreshMiniWishlist = refreshMiniWishlist;

document.addEventListener('click', async (e) => {
    const removeBtn = e.target.closest('[data-wishlist-remove]');
    if (!removeBtn) {
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    const url = removeBtn.dataset.removeUrl;
    if (!url) {
        return;
    }

    removeBtn.disabled = true;

    try {
        const res = await fetch(url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': wishlistCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!res.ok) {
            return;
        }

        const data = await res.json();
        const productId = removeBtn.closest('[data-product-id]')?.dataset.productId;

        if (productId) {
            document.querySelectorAll(`[data-product-wishlist][data-product-id="${productId}"]`).forEach((btn) => {
                btn.classList.remove('is-active');
                const icon = btn.querySelector('svg');
                if (icon) {
                    icon.setAttribute('fill', 'none');
                }
            });
        }

        updateWishlistCountBadge(data.count ?? 0);
        await refreshMiniWishlist();
        window.dispatchEvent(new CustomEvent('wishlist:updated', { detail: { count: data.count ?? 0 } }));
    } catch {
        //
    } finally {
        removeBtn.disabled = false;
    }
});

window.addEventListener('wishlist:updated', (e) => {
    if (typeof e.detail?.count === 'number') {
        updateWishlistCountBadge(e.detail.count);
    }
    refreshMiniWishlist();
});
