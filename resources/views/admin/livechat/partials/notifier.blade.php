{{-- Utilitas bersama halaman live chat petugas: nada notifikasi, notifikasi desktop, penanda judul tab & pembaca feed. --}}
@push('scripts')
    <script>
        window.LiveChatKit ??= (() => {
            const KEY = 'mui_livechat_sound';
            const LOGO = @js($site['logo_url']);
            const baseTitle = document.title;
            let ctx = null;
            let unseen = 0;

            const enabled = () => { try { return localStorage.getItem(KEY) !== '0'; } catch { return true; } };
            const setEnabled = (value) => { try { localStorage.setItem(KEY, value ? '1' : '0'); } catch { /* abaikan */ } };
            const audio = () => {
                try {
                    ctx ??= new (window.AudioContext || window.webkitAudioContext)();
                    if (ctx.state === 'suspended') ctx.resume().catch(() => {});
                    return ctx;
                } catch {
                    return null;
                }
            };
            // Peramban menahan audio sampai ada interaksi: siapkan AudioContext pada klik / tombol pertama.
            ['pointerdown', 'keydown'].forEach((type) => window.addEventListener(type, () => audio(), { once: true, passive: true }));

            const tone = (steps, duration, volume = 0.2) => {
                if (!enabled()) return;
                const c = audio();
                if (!c || c.state !== 'running') return;
                try {
                    const osc = c.createOscillator();
                    const gain = c.createGain();
                    osc.connect(gain);
                    gain.connect(c.destination);
                    steps.forEach(([freq, at]) => osc.frequency.setValueAtTime(freq, c.currentTime + at));
                    gain.gain.setValueAtTime(volume, c.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, c.currentTime + duration);
                    osc.start(c.currentTime);
                    osc.stop(c.currentTime + duration);
                } catch { /* audio tidak tersedia */ }
            };

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && unseen) {
                    unseen = 0;
                    document.title = baseTitle;
                }
            });

            return {
                enabled,
                setEnabled,
                unlock: audio,
                /** Bel antrian baru (pengunjung masuk antrian). */
                bell: () => tone([[800, 0], [1200, 0.15]], 0.5),
                /** Nada pesan baru dari pengunjung. */
                chime: () => tone([[587.33, 0], [880, 0.1]], 0.35),
                async requestPermission() {
                    try {
                        if ('Notification' in window && Notification.permission === 'default') await Notification.requestPermission();
                    } catch { /* abaikan */ }
                },
                notify(title, body) {
                    try {
                        if ('Notification' in window && Notification.permission === 'granted') new Notification(title, { body, icon: LOGO, tag: 'mui-livechat' });
                    } catch { /* abaikan */ }
                },
                /** Tambah penanda "(n)" di judul tab ketika halaman tidak sedang dilihat. */
                attention(count = 1) {
                    if (!document.hidden) return;
                    unseen += count;
                    document.title = `(${unseen}) ${baseTitle}`;
                },
                readFeed(doc) {
                    try { return JSON.parse(doc.getElementById('lc-feed')?.textContent || 'null'); } catch { return null; }
                },
                /**
                 * Tanda tangan ringkas yang setara dengan respons poll-overview
                 * (jumlah antrian | sesi aktif milik saya | id antrian terbaru), dihitung dari feed halaman.
                 */
                signature(feed) {
                    const latest = [...(feed?.waiting ?? [])].sort((a, b) => String(b.masuk).localeCompare(String(a.masuk)) || b.id - a.id)[0];
                    return [feed?.waiting?.length ?? 0, (feed?.active ?? []).filter((s) => s.mine).length, latest?.id ?? ''].join('|');
                },
                overviewSignature: (d) => [d.waiting_count, d.my_active_count, d.latest_waiting_id ?? ''].join('|'),
                /** Ambil ulang halaman (HTML) lalu baca ringkasan sesi terbaru dari #lc-feed. */
                async fetchFeed(url) {
                    const res = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw Object.assign(new Error('Gagal memuat data live chat.'), { status: res.status });
                    return this.readFeed(new DOMParser().parseFromString(await res.text(), 'text/html'));
                },
                /** "3 menit lalu" dsb. dari waktu ISO. */
                ago(iso, now = Date.now()) {
                    if (!iso) return '—';
                    const sec = Math.max(0, Math.round((now - new Date(iso).getTime()) / 1000));
                    if (sec < 45) return 'baru saja';
                    const min = Math.round(sec / 60);
                    if (min < 60) return `${min} menit lalu`;
                    const hour = Math.floor(min / 60);
                    if (hour < 24) return `${hour} jam lalu`;
                    return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                },
                minutesSince(iso, now = Date.now()) {
                    return iso ? Math.max(0, Math.floor((now - new Date(iso).getTime()) / 60000)) : 0;
                },
                clock(iso) {
                    return iso ? new Date(iso).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':') : '';
                },
            };
        })();
    </script>
@endpush
