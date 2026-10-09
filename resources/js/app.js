import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import { Coordinates, CalculationMethod, PrayerTimes, Madhab } from 'adhan';
import registerChatWidget from './chat';

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.plugin(intersect);
registerChatWidget(Alpine);

/* ------------------------------------------------------------------
 | Tanggal Masehi & Hijriah
 * ------------------------------------------------------------------ */
const HIJRI_MONTHS = ['Muharram', 'Safar', 'Rabiul Awal', 'Rabiul Akhir', 'Jumadil Awal', 'Jumadil Akhir', 'Rajab', "Sya'ban", 'Ramadhan', 'Syawal', "Dzulqa'dah", 'Dzulhijjah'];

export function hijriDate(date = new Date()) {
    try {
        const parts = new Intl.DateTimeFormat('en-u-ca-islamic-umalqura', { day: 'numeric', month: 'numeric', year: 'numeric' }).formatToParts(date);
        const get = (t) => parseInt(parts.find((p) => p.type === t)?.value ?? '0', 10);
        return `${get('day')} ${HIJRI_MONTHS[get('month') - 1]} ${get('year')} H`;
    } catch {
        return '';
    }
}

Alpine.data('clock', () => ({
    masehi: '',
    hijri: '',
    time: '',
    init() {
        const tick = () => {
            const now = new Date();
            this.masehi = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            this.hijri = hijriDate(now);
            this.time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        };
        tick();
        setInterval(tick, 30000);
    },
}));

/* ------------------------------------------------------------------
 | Jadwal sholat (perhitungan lokal, parameter setara Kemenag RI)
 * ------------------------------------------------------------------ */
Alpine.data('prayerTimes', (lat = -1.6971, lng = 103.2653, city = 'Muara Bulian') => ({
    items: [],
    next: null,
    countdown: '',
    tomorrow: false,
    city,
    timer: null,
    init() {
        this.compute();
        this.timer = setInterval(() => this.compute(), 1000);
    },
    destroy() {
        clearInterval(this.timer);
    },
    compute() {
        const coords = new Coordinates(parseFloat(lat), parseFloat(lng));
        const params = CalculationMethod.Singapore(); // Subuh 20°, Isya 18° (setara Kemenag RI)
        params.madhab = Madhab.Shafi;
        params.adjustments = { fajr: 2, sunrise: -2, dhuhr: 3, asr: 2, maghrib: 2, isha: 2 }; // ihtiyath
        const fmt = (d) => d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':');
        const schedule = (day) => {
            const pt = new PrayerTimes(coords, day, params);
            return [
                { key: 'imsak', name: 'Imsak', at: new Date(pt.fajr.getTime() - 10 * 60000) },
                { key: 'fajr', name: 'Subuh', at: pt.fajr, main: true },
                { key: 'sunrise', name: 'Terbit', at: pt.sunrise },
                { key: 'dhuha', name: 'Dhuha', at: new Date(pt.sunrise.getTime() + 20 * 60000) },
                { key: 'dhuhr', name: day.getDay() === 5 ? "Jum'at" : 'Dzuhur', at: pt.dhuhr, main: true },
                { key: 'asr', name: 'Ashar', at: pt.asr, main: true },
                { key: 'maghrib', name: 'Maghrib', at: pt.maghrib, main: true },
                { key: 'isha', name: 'Isya', at: pt.isha, main: true },
            ];
        };

        const now = new Date();
        let list = schedule(now);
        let next = list.find((p) => p.main && p.at > now);
        // Lewat Isya: tampilkan jadwal esok hari agar sorotan & hitung mundur menunjuk Subuh yang sama.
        this.tomorrow = !next;
        if (!next) {
            list = schedule(new Date(now.getTime() + 86400000));
            next = list.find((p) => p.main);
        }

        const diff = Math.max(0, next.at - now);
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        this.countdown = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        this.next = { ...next, time: fmt(next.at) };
        this.items = list.map((p) => ({ ...p, time: fmt(p.at), active: p.key === next.key }));
    },
}));

/* ------------------------------------------------------------------
 | Slider hero
 * ------------------------------------------------------------------ */
Alpine.data('slider', (count = 1, interval = 7000) => ({
    current: 0,
    count,
    timer: null,
    paused: false,
    progressKey: 0,
    init() {
        this.start();
    },
    destroy() {
        clearInterval(this.timer);
    },
    start() {
        clearInterval(this.timer);
        if (this.count < 2) return;
        this.timer = setInterval(() => !this.paused && this.go(this.current + 1, false), interval);
    },
    go(i, manual = true) {
        this.current = (i + this.count) % this.count;
        this.progressKey++;
        if (manual) this.start();
    },
    touchStartX: 0,
    onTouchStart(e) { this.touchStartX = e.touches[0].clientX; },
    onTouchEnd(e) {
        const dx = e.changedTouches[0].clientX - this.touchStartX;
        if (Math.abs(dx) > 40) this.go(this.current + (dx < 0 ? 1 : -1));
    },
}));

/* ------------------------------------------------------------------
 | Penghitung angka animatif
 * ------------------------------------------------------------------ */
Alpine.data('counter', (target = 0) => ({
    value: 0,
    run() {
        const start = performance.now();
        const duration = 1600;
        const step = (t) => {
            const p = Math.min(1, (t - start) / duration);
            this.value = Math.round(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    },
    get display() {
        return this.value.toLocaleString('id-ID');
    },
}));

/* ------------------------------------------------------------------
 | Bagikan & salin tautan
 * ------------------------------------------------------------------ */
Alpine.data('share', (title = document.title, url = location.href) => ({
    copied: false,
    async native() {
        if (navigator.share) {
            try { await navigator.share({ title, url }); } catch { /* dibatalkan */ }
        } else {
            this.copy();
        }
    },
    async copy() {
        try {
            await navigator.clipboard.writeText(url);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch { /* abaikan */ }
    },
    link(network) {
        const u = encodeURIComponent(url);
        const t = encodeURIComponent(title);
        return {
            whatsapp: `https://wa.me/?text=${t}%20${u}`,
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${u}`,
            x: `https://twitter.com/intent/tweet?text=${t}&url=${u}`,
            telegram: `https://t.me/share/url?url=${u}&text=${t}`,
        }[network];
    },
}));

/* ------------------------------------------------------------------
 | Progres membaca artikel
 * ------------------------------------------------------------------ */
Alpine.data('readingProgress', () => ({
    progress: 0,
    init() {
        const onScroll = () => {
            const el = this.$refs.article ?? document.documentElement;
            const rect = el.getBoundingClientRect();
            const total = rect.height - window.innerHeight;
            this.progress = total > 0 ? Math.min(100, Math.max(0, (-rect.top / total) * 100)) : 0;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    },
}));

/* ------------------------------------------------------------------
 | Pencarian cepat (Ctrl+K)
 * ------------------------------------------------------------------ */
Alpine.data('searchModal', () => ({
    open: false,
    init() {
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.show();
            }
            if (e.key === 'Escape') this.open = false;
        });
        window.addEventListener('open-search', () => this.show());
    },
    show() {
        this.open = true;
        this.$nextTick(() => this.$refs.q?.focus());
    },
}));

/* ------------------------------------------------------------------
 | Header: transparan di atas hero, sembunyi saat scroll ke bawah
 * ------------------------------------------------------------------ */
Alpine.data('siteHeader', (solid = false) => ({
    scrolled: solid,
    hidden: false,
    mobile: false,
    last: 0,
    init() {
        const onScroll = () => {
            const y = window.scrollY;
            this.scrolled = solid || y > 20;
            this.hidden = y > 400 && y > this.last && !this.mobile;
            this.last = y;
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
        this.$watch('mobile', (v) => document.documentElement.classList.toggle('overflow-hidden', v));
    },
}));

/* ------------------------------------------------------------------
 | Animasi muncul saat scroll
 * ------------------------------------------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -60px 0px' });
    document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
});

Alpine.start();
