const form = document.getElementById('checkout-form');
let loyaltyInfo = null;

const headers = (token) => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    Authorization: `Bearer ${token}`,
    'X-Session-Id': form?.dataset.sessionId,
});

const loadLoyalty = async (token) => {
    const section = document.getElementById('loyalty-section');
    if (!section || !token) return;

    const res = await fetch(`${form.dataset.api}/loyalty/balance`, {
        headers: headers(token),
    });

    if (!res.ok) return;

    loyaltyInfo = await res.json();
    section.classList.remove('hidden');

    document.getElementById('loyalty-balance').textContent =
        `${__('balance')}: ${loyaltyInfo.points} نقطة (الحد الأقصى للاستبدال: حسب السلة)`;

    if (loyaltyInfo.vip_level && loyaltyInfo.vip_discount_percent > 0) {
        const vip = document.getElementById('loyalty-vip');
        vip.textContent = `مستوى ${loyaltyInfo.vip_level.name} — خصم VIP ${loyaltyInfo.vip_discount_percent}%`;
        vip.classList.remove('hidden');
    }

    const input = document.getElementById('loyalty_points');
    input?.addEventListener('input', () => previewLoyalty(token));
};

const previewLoyalty = async (token) => {
    const points = parseInt(document.getElementById('loyalty_points')?.value || '0', 10);
    const preview = document.getElementById('loyalty-discount-preview');

    if (!points) {
        preview.textContent = '0.00 ج.م';
        return;
    }

    const res = await fetch(`${form.dataset.api}/loyalty/preview`, {
        method: 'POST',
        headers: headers(token),
        body: JSON.stringify({ points }),
    });

    const data = await res.json();
    preview.textContent = `${Number(data.discount_value || 0).toFixed(2)} ج.م`;
    if (data.message) {
        preview.title = data.message;
    }
};

function __(key) {
    return key === 'balance' ? 'رصيدك' : key;
}

if (form) {
    const token = localStorage.getItem('api_token');
    if (token) {
        loadLoyalty(token);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const authToken = localStorage.getItem('api_token');
        if (!authToken) {
            alert('يجب تسجيل الدخول أولاً');
            window.location.href = '/admin/login';
            return;
        }

        const fd = new FormData(form);
        const shippingAddress = {
            first_name: fd.get('first_name'),
            last_name: fd.get('last_name'),
            phone: fd.get('phone'),
            address_line_1: fd.get('address_line_1'),
            city: fd.get('city'),
            country: fd.get('country'),
        };

        const loyaltyPoints = parseInt(fd.get('loyalty_points') || '0', 10);

        const res = await fetch(`${form.dataset.api}/checkout`, {
            method: 'POST',
            headers: headers(authToken),
            body: JSON.stringify({
                shipping_address: shippingAddress,
                billing_address: shippingAddress,
                shipping_rate_id: parseInt(fd.get('shipping_rate_id'), 10),
                payment_gateway: fd.get('payment_gateway'),
                loyalty_points: loyaltyPoints,
                notes: fd.get('notes'),
            }),
        });

        const data = await res.json();
        const err = document.getElementById('checkout-error');

        if (res.ok) {
            const earned = data.loyalty_points_earned ?? data.data?.loyalty_points_earned;
            let msg = `تم إنشاء الطلب: ${data.order_number || data.data?.order_number || 'نجاح'}`;
            if (earned) {
                msg += `\nستحصل على ${earned} نقطة ولاء`;
            }
            alert(msg);
            window.location.href = '/';
        } else {
            err.textContent = data.message || 'فشل إتمام الطلب';
            err.classList.remove('hidden');
        }
    });
}
