@verbatim
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#0f766e">
    <title>QuestMap — A Coruña</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <style>
        :root {
            --bg: #0b1120; --panel: #131c2e; --panel-2: #1b2740;
            --ink: #e8eef7; --muted: #93a1b8; --line: #26324b;
            --brand: #14b8a6; --brand-ink: #032420;
            --gold: #f5b301; --danger: #ef4444; --ok: #22c55e;
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg); color: var(--ink); overflow: hidden;
        }
        #map { position: absolute; inset: 0; z-index: 1; background: #0b1120; }

        .topbar {
            position: absolute; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; align-items: center; gap: 10px; padding: 10px 14px;
            background: linear-gradient(180deg, rgba(11,17,32,.96), rgba(11,17,32,.72) 70%, rgba(11,17,32,0));
            pointer-events: none;
        }
        .topbar > * { pointer-events: auto; }
        .logo { font-weight: 800; letter-spacing: .3px; font-size: 18px; }
        .logo span { color: var(--brand); }
        .spacer { flex: 1; }
        .pill {
            display: inline-flex; align-items: center; gap: 6px; background: var(--panel);
            border: 1px solid var(--line); border-radius: 999px; padding: 6px 12px;
            font-size: 13px; font-weight: 600;
        }
        .pill b { color: var(--gold); }
        .icon-btn {
            width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line);
            background: var(--panel); color: var(--ink); font-size: 18px; cursor: pointer;
            display: grid; place-items: center;
        }

        .fab {
            position: absolute; right: 16px; bottom: 24px; z-index: 1000;
            width: 56px; height: 56px; border-radius: 50%; border: none;
            background: var(--brand); color: var(--brand-ink); font-size: 24px;
            box-shadow: 0 8px 24px rgba(20,184,166,.4); cursor: pointer;
        }
        .fab.secondary {
            bottom: 92px; width: 48px; height: 48px; font-size: 20px;
            background: var(--panel); color: var(--ink); border: 1px solid var(--line);
            box-shadow: 0 6px 18px rgba(0,0,0,.4);
        }

        .sheet {
            position: absolute; left: 0; right: 0; bottom: 0; z-index: 1200;
            background: var(--panel); border-top-left-radius: 20px; border-top-right-radius: 20px;
            border-top: 1px solid var(--line); box-shadow: 0 -10px 40px rgba(0,0,0,.5);
            transform: translateY(110%); transition: transform .28s cubic-bezier(.2,.8,.2,1);
            max-height: 82vh; overflow-y: auto; padding: 8px 18px 24px;
        }
        .sheet.open { transform: translateY(0); }
        .grabber { width: 44px; height: 5px; border-radius: 3px; background: var(--line); margin: 6px auto 12px; }
        .cat {
            display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .6px; color: var(--brand); background: rgba(20,184,166,.12);
            border: 1px solid rgba(20,184,166,.3); padding: 3px 9px; border-radius: 999px;
        }
        .sheet h2 { margin: 10px 0 6px; font-size: 22px; }
        .sheet p.desc { color: var(--muted); line-height: 1.5; margin: 6px 0 14px; }
        .meta { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; font-size: 14px; }
        .meta div b { color: var(--gold); }
        .meta .lbl { color: var(--muted); font-size: 12px; display: block; }

        .btn {
            width: 100%; padding: 15px; border-radius: 14px; border: none; font-size: 16px;
            font-weight: 700; cursor: pointer; background: var(--brand); color: var(--brand-ink);
        }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn.ghost { background: var(--panel-2); color: var(--ink); border: 1px solid var(--line); }
        .btn.gold { background: var(--gold); color: #3a2b00; }
        .hint { text-align: center; color: var(--muted); font-size: 13px; margin-top: 10px; }

        .overlay {
            position: absolute; inset: 0; z-index: 2000; background: rgba(4,8,16,.72);
            backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .overlay.open { display: flex; }
        .card {
            width: 100%; max-width: 400px; background: var(--panel); border: 1px solid var(--line);
            border-radius: 20px; padding: 26px; box-shadow: 0 20px 60px rgba(0,0,0,.6);
            max-height: 88vh; overflow-y: auto;
        }
        .card h1 { margin: 0 0 4px; font-size: 26px; }
        .card h1 span { color: var(--brand); }
        .card .sub { color: var(--muted); margin: 0 0 20px; font-size: 14px; }
        label { display: block; font-size: 13px; color: var(--muted); margin: 12px 0 6px; font-weight: 600; }
        input[type=email], input[type=password], input[type=text] {
            width: 100%; padding: 13px; border-radius: 12px; border: 1px solid var(--line);
            background: var(--panel-2); color: var(--ink); font-size: 15px;
        }
        .row-btns { display: flex; gap: 10px; margin-top: 20px; }
        .row-btns .btn { flex: 1; }
        .seed { margin-top: 16px; font-size: 12px; color: var(--muted); text-align: center; line-height: 1.6; }
        .seed a { color: var(--brand); cursor: pointer; text-decoration: underline; }

        .lb-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--line); }
        .lb-rank { width: 28px; text-align: center; font-weight: 800; color: var(--muted); }
        .lb-rank.top { color: var(--gold); }
        .lb-name { flex: 1; font-weight: 600; }
        .lb-xp { color: var(--gold); font-weight: 700; }
        .lb-lvl { font-size: 12px; color: var(--muted); }
        .tabs { display: flex; gap: 8px; margin: 4px 0 14px; }
        .tab { flex: 1; text-align: center; padding: 8px; border-radius: 10px; background: var(--panel-2);
               border: 1px solid var(--line); font-size: 13px; font-weight: 600; cursor: pointer; color: var(--muted); }
        .tab.active { color: var(--brand); border-color: var(--brand); }

        #toast {
            position: absolute; left: 50%; bottom: 120px; transform: translateX(-50%) translateY(20px);
            z-index: 3000; background: var(--panel-2); color: var(--ink); padding: 13px 18px;
            border-radius: 12px; border: 1px solid var(--line); font-size: 14px; font-weight: 600;
            opacity: 0; transition: all .25s; pointer-events: none; max-width: 90vw; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.5);
        }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        #toast.ok { border-color: var(--ok); }
        #toast.err { border-color: var(--danger); }

        .demo-banner {
            position: absolute; top: 62px; left: 50%; transform: translateX(-50%); z-index: 999;
            background: rgba(245,179,1,.14); border: 1px solid rgba(245,179,1,.4); color: var(--gold);
            font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 999px; display: none;
        }
        .demo-banner.show { display: block; }

        .q-pin {
            width: 34px; height: 34px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
            background: var(--brand); border: 2px solid #fff; box-shadow: 0 3px 8px rgba(0,0,0,.5);
            display: grid; place-items: center;
        }
        .q-pin.done { background: var(--gold); }
        .q-pin span { transform: rotate(45deg); font-size: 15px; }
        .me-dot { width: 18px; height: 18px; border-radius: 50%; background: #3b82f6; border: 3px solid #fff; box-shadow: 0 0 0 6px rgba(59,130,246,.25); }
    </style>
</head>
<body>
    <div id="map"></div>

    <div class="topbar">
        <div class="logo">Quest<span>Map</span></div>
        <div class="spacer"></div>
        <div class="pill" id="xpPill" style="display:none">Nv <b id="lvl">1</b> · <span id="xp">0</span> XP</div>
        <button class="icon-btn" id="lbBtn" title="Clasificación" style="display:none">🏆</button>
        <button class="icon-btn" id="logoutBtn" title="Salir" style="display:none">⎋</button>
    </div>

    <div class="demo-banner" id="demoBanner">🧭 Modo demo: toca el mapa para moverte</div>

    <button class="fab secondary" id="demoBtn" title="Modo demo">🧭</button>
    <button class="fab" id="locBtn" title="Mi ubicación">📍</button>

    <div class="sheet" id="sheet">
        <div class="grabber"></div>
        <span class="cat" id="qCat">—</span>
        <h2 id="qTitle">—</h2>
        <p class="desc" id="qDesc"></p>
        <div class="meta">
            <div><span class="lbl">Recompensa</span><b id="qXp">—</b> XP</div>
            <div><span class="lbl">Distancia</span><span id="qDist">—</span></div>
            <div><span class="lbl">Tipo</span><span id="qType">—</span></div>
        </div>
        <div id="qAction"></div>
        <div class="hint" id="qHint"></div>
    </div>

    <div class="overlay open" id="authOverlay">
        <div class="card">
            <h1>Quest<span>Map</span></h1>
            <p class="sub">Aventuras urbanas por A Coruña ✨</p>
            <div id="authForm">
                <label>Email</label>
                <input type="email" id="email" value="alba@questmap.test" autocomplete="username">
                <label>Contraseña</label>
                <input type="password" id="password" value="password" autocomplete="current-password">
                <div class="row-btns">
                    <button class="btn" id="loginBtn">Entrar</button>
                </div>
                <div class="seed">
                    Usuarios demo: alba, brais, carmela, diego, uxia @questmap.test · pass <b>password</b><br>
                    <a id="toRegister">¿Crear una cuenta nueva?</a>
                </div>
            </div>
            <div id="regForm" style="display:none">
                <label>Nombre</label>
                <input type="text" id="rName" placeholder="Tu nombre">
                <label>Email</label>
                <input type="email" id="rEmail" placeholder="tu@email.com">
                <label>Contraseña (mín. 8)</label>
                <input type="password" id="rPass" placeholder="········">
                <div class="row-btns">
                    <button class="btn ghost" id="backLogin">Volver</button>
                    <button class="btn" id="registerBtn">Crear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="overlay" id="lbOverlay">
        <div class="card">
            <h1>🏆 Clasificación</h1>
            <p class="sub">A Coruña</p>
            <div class="tabs">
                <div class="tab active" data-period="week">Semana</div>
                <div class="tab" data-period="month">Mes</div>
                <div class="tab" data-period="all">Global</div>
            </div>
            <div id="lbList"></div>
            <div class="row-btns"><button class="btn ghost" id="lbClose">Cerrar</button></div>
        </div>
    </div>

    <div id="toast"></div>

    <input type="file" id="photoInput" accept="image/*" capture="environment" style="display:none">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
    const API = location.origin + '/api';
    const CORUNA = [43.3623, -8.4115];
    let token = localStorage.getItem('qm_token') || null;
    let me = null;
    let map, meMarker = null, questMarkers = {}, current = null;
    let myPos = { lat: CORUNA[0], lng: CORUNA[1] };
    let demoMode = false;

    async function api(path, { method = 'GET', body = null, form = null } = {}) {
        const headers = { 'Accept': 'application/json' };
        if (token) headers['Authorization'] = 'Bearer ' + token;
        let opts = { method, headers };
        if (form) { opts.body = form; }
        else if (body) { headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const res = await fetch(API + path, opts);
        let data = null;
        try { data = await res.json(); } catch (e) {}
        if (!res.ok) {
            const msg = (data && data.message) || ('Error ' + res.status);
            const err = new Error(msg); err.status = res.status; err.data = data;
            throw err;
        }
        return data;
    }

    function toast(msg, kind = '') {
        const t = document.getElementById('toast');
        t.textContent = msg; t.className = 'show ' + kind;
        clearTimeout(t._t); t._t = setTimeout(() => t.className = kind, 2800);
    }

    function initMap() {
        map = L.map('map', { zoomControl: false }).setView(CORUNA, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap'
        }).addTo(map);
        L.control.zoom({ position: 'bottomleft' }).addTo(map);
        map.on('click', (e) => {
            if (!demoMode) return;
            setMyPos(e.latlng.lat, e.latlng.lng, false);
            loadNearby();
            toast('Ubicación demo actualizada');
        });
    }

    function setMyPos(lat, lng, recenter = true) {
        myPos = { lat, lng };
        if (!meMarker) {
            meMarker = L.marker([lat, lng], {
                icon: L.divIcon({ className: '', html: '<div class="me-dot"></div>', iconSize: [18, 18] })
            }).addTo(map);
        } else meMarker.setLatLng([lat, lng]);
        if (recenter) map.setView([lat, lng], 15);
    }

    function pinIcon(done) {
        return L.divIcon({
            className: '', iconSize: [34, 42], iconAnchor: [17, 40],
            html: '<div class="q-pin' + (done ? ' done' : '') + '"><span>' + (done ? '✓' : '★') + '</span></div>'
        });
    }

    async function loadNearby() {
        try {
            const r = await api(`/quests/nearby?lat=${myPos.lat}&lng=${myPos.lng}&radius=20000`);
            const quests = r.data || [];
            Object.values(questMarkers).forEach(m => map.removeLayer(m));
            questMarkers = {};
            quests.forEach(q => {
                if (!q.location) return;
                const m = L.marker([q.location.lat, q.location.lng], { icon: pinIcon(false) })
                    .addTo(map).on('click', () => openQuest(q));
                questMarkers[q.id] = m;
            });
            if (quests.length) toast(quests.length + ' misiones cerca');
            else toast('No hay misiones en 20 km. Prueba el modo demo 🧭', 'err');
        } catch (e) { toast(e.message, 'err'); }
    }

    function openQuest(q) {
        current = q;
        document.getElementById('qCat').textContent = q.category;
        document.getElementById('qTitle').textContent = q.title;
        document.getElementById('qDesc').textContent = q.description;
        document.getElementById('qXp').textContent = q.xp_reward;
        document.getElementById('qDist').textContent = q.dist_m != null ? Math.round(q.dist_m) + ' m' : '—';
        const typeLabel = { photo_ai: '📷 Foto', checkin: '📍 Check-in', data_input: '✍️ Dato' }[q.validation_type] || q.validation_type;
        document.getElementById('qType').textContent = typeLabel;

        const action = document.getElementById('qAction');
        const hint = document.getElementById('qHint');
        action.innerHTML = ''; hint.textContent = '';

        if (q.validation_type === 'checkin') {
            const b = document.createElement('button');
            b.className = 'btn'; b.textContent = '📍 Hacer check-in aquí';
            b.onclick = () => doCheckin(q);
            action.appendChild(b);
            hint.textContent = 'Debes estar a menos de ' + q.geofence_radius_m + ' m del punto.';
        } else if (q.validation_type === 'photo_ai') {
            const b = document.createElement('button');
            b.className = 'btn gold'; b.textContent = '📷 Subir foto';
            b.onclick = () => { document.getElementById('photoInput').click(); };
            action.appendChild(b);
            hint.textContent = 'Una IA validará tu foto y te dará los XP.';
        } else {
            hint.textContent = 'Este tipo de misión se completa desde la app móvil nativa.';
        }
        document.getElementById('sheet').classList.add('open');
    }

    function closeSheet() { document.getElementById('sheet').classList.remove('open'); }

    function submitCoords(q) {
        return demoMode ? { lat: q.location.lat, lng: q.location.lng } : myPos;
    }

    async function doCheckin(q) {
        try {
            const coords = submitCoords(q);
            const r = await api(`/quests/${q.id}/checkin`, { method: 'POST', body: coords });
            toast('✅ +' + r.completion.xp_awarded + ' XP · ' + q.title, 'ok');
            if (questMarkers[q.id]) questMarkers[q.id].setIcon(pinIcon(true));
            closeSheet(); await refreshMe();
        } catch (e) { toast(e.message, 'err'); }
    }

    document.getElementById('photoInput').addEventListener('change', async (ev) => {
        const file = ev.target.files[0]; ev.target.value = '';
        if (!file || !current) return;
        const q = current;
        try {
            const coords = submitCoords(q);
            const fd = new FormData();
            fd.append('photo', file); fd.append('lat', coords.lat); fd.append('lng', coords.lng);
            await api(`/quests/${q.id}/submit`, { method: 'POST', form: fd });
            toast('📤 Foto enviada. La IA la validará en segundos…', 'ok');
            closeSheet();
        } catch (e) { toast(e.message, 'err'); }
    });

    document.getElementById('locBtn').onclick = () => {
        if (demoMode) { toggleDemo(); }
        toast('Buscando tu ubicación…');
        navigator.geolocation.getCurrentPosition(async (pos) => {
            setMyPos(pos.coords.latitude, pos.coords.longitude);
            try { await api('/me/location', { method: 'POST', body: myPos }); } catch (e) {}
            await refreshMe(); loadNearby();
        }, () => toast('No se pudo obtener el GPS. Usa el modo demo 🧭', 'err'), { enableHighAccuracy: true, timeout: 8000 });
    };

    function toggleDemo() {
        demoMode = !demoMode;
        document.getElementById('demoBanner').classList.toggle('show', demoMode);
        document.getElementById('demoBtn').style.background = demoMode ? 'var(--gold)' : 'var(--panel)';
        document.getElementById('demoBtn').style.color = demoMode ? '#3a2b00' : 'var(--ink)';
        if (demoMode) { setMyPos(CORUNA[0], CORUNA[1]); loadNearby(); toast('Modo demo activado. Toca el mapa para moverte'); }
    }
    document.getElementById('demoBtn').onclick = toggleDemo;

    async function refreshMe() {
        try {
            const r = await api('/me'); me = r.data;
            document.getElementById('lvl').textContent = me.level;
            document.getElementById('xp').textContent = me.xp;
        } catch (e) {}
    }

    function showApp() {
        document.getElementById('authOverlay').classList.remove('open');
        ['xpPill', 'lbBtn', 'logoutBtn'].forEach(id => document.getElementById(id).style.display = '');
        refreshMe(); loadNearby();
    }

    document.getElementById('loginBtn').onclick = async () => {
        try {
            const r = await api('/auth/login', { method: 'POST', body: {
                email: document.getElementById('email').value, password: document.getElementById('password').value
            }});
            token = r.token; localStorage.setItem('qm_token', token);
            toast('¡Hola, ' + r.user.name + '!', 'ok'); showApp();
        } catch (e) { toast(e.message, 'err'); }
    };

    document.getElementById('registerBtn').onclick = async () => {
        try {
            const pass = document.getElementById('rPass').value;
            const r = await api('/auth/register', { method: 'POST', body: {
                name: document.getElementById('rName').value,
                email: document.getElementById('rEmail').value,
                password: pass, password_confirmation: pass
            }});
            token = r.token; localStorage.setItem('qm_token', token);
            toast('¡Cuenta creada! Bienvenido/a', 'ok'); showApp();
        } catch (e) { toast(e.message, 'err'); }
    };

    document.getElementById('toRegister').onclick = () => {
        document.getElementById('authForm').style.display = 'none';
        document.getElementById('regForm').style.display = '';
    };
    document.getElementById('backLogin').onclick = () => {
        document.getElementById('regForm').style.display = 'none';
        document.getElementById('authForm').style.display = '';
    };

    document.getElementById('logoutBtn').onclick = async () => {
        try { await api('/auth/logout', { method: 'POST' }); } catch (e) {}
        token = null; localStorage.removeItem('qm_token');
        location.reload();
    };

    async function loadLb(period) {
        try {
            const r = await api('/leaderboard?period=' + period);
            const list = document.getElementById('lbList');
            const rows = r.data || [];
            if (!rows.length) { list.innerHTML = '<p class="sub">Aún no hay puntuaciones en este periodo.</p>'; return; }
            list.innerHTML = rows.map(u =>
                '<div class="lb-row"><div class="lb-rank' + (u.rank <= 3 ? ' top' : '') + '">' + u.rank + '</div>' +
                '<div class="lb-name">' + u.name + ' <span class="lb-lvl">· Nv ' + u.level + '</span></div>' +
                '<div class="lb-xp">' + u.period_xp + ' XP</div></div>').join('');
        } catch (e) { toast(e.message, 'err'); }
    }
    document.getElementById('lbBtn').onclick = () => { document.getElementById('lbOverlay').classList.add('open'); loadLb('week'); };
    document.getElementById('lbClose').onclick = () => document.getElementById('lbOverlay').classList.remove('open');
    document.querySelectorAll('.tab').forEach(t => t.onclick = () => {
        document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active'); loadLb(t.dataset.period);
    });

    initMap();
    if (token) { showApp(); }
    </script>
</body>
</html>
@endverbatim
