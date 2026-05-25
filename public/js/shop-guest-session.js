function shopCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function shopSessionId() {
    const meta = document.querySelector('meta[name="shop-session-id"]')?.getAttribute('content') || '';
    if (meta) {
        return meta;
    }

    return sessionStorage.getItem('shop_session_id') || '';
}

function persistShopSessionId(sessionId) {
    if (!sessionId) {
        return;
    }

    sessionStorage.setItem('shop_session_id', sessionId);

    const meta = document.querySelector('meta[name="shop-session-id"]');
    if (meta) {
        meta.setAttribute('content', sessionId);
    }
}

function shopApiBase() {
    return document.body?.dataset.shopApi || '/api/v1';
}

function shopGuestFetchHeaders(extra = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': shopCsrfToken(),
        ...extra,
    };

    const sessionId = shopSessionId();
    if (sessionId) {
        headers['X-Shop-Session-Id'] = sessionId;
    }

    return headers;
}

window.shopCsrfToken = shopCsrfToken;
window.shopSessionId = shopSessionId;
window.persistShopSessionId = persistShopSessionId;
window.shopApiBase = shopApiBase;
window.shopGuestFetchHeaders = shopGuestFetchHeaders;
