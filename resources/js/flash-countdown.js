function formatCountdown(ms) {
    if (ms <= 0) {
        return '';
    }
    const s = Math.floor(ms / 1000);
    const d = Math.floor(s / 86400);
    const h = Math.floor((s % 86400) / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;
    const parts = [];
    if (d > 0) parts.push(`${d}ي`);
    parts.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`);
    return parts.join(' ');
}

function tickCountdowns() {
    document.querySelectorAll('.flash-countdown[data-ends]').forEach((el) => {
        const ends = new Date(el.dataset.ends).getTime();
        const remaining = ends - Date.now();
        const label = el.dataset.label || 'ينتهي خلال: ';
        el.textContent = remaining > 0 ? label + formatCountdown(remaining) : (el.dataset.ended || 'انتهى العرض');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    tickCountdowns();
    setInterval(tickCountdowns, 1000);
});
