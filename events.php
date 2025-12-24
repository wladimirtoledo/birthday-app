<?php
// events.php
require_once 'includes/functions.php';

// 1. SEGURIDAD DE SESIÓN (PHP)
if (session_status() === PHP_SESSION_NONE) session_start();
// Validar y sanear variables de sesión para evitar inyecciones accidentales
$userId = (int)($_SESSION['user_id'] ?? 0);
$userRole = preg_replace('/[^a-z_]/', '', strtolower((string)($_SESSION['user_role'] ?? 'guest')));
$userStatus = preg_replace('/[^a-z_]/', '', strtolower((string)($_SESSION['user_status'] ?? 'active')));

// Regla 12: Bloqueo de acceso
if ($userId === 0 || in_array($userStatus, ['banned_login', 'banned_view'])) {
    header("Location: index.php");
    exit;
}

// Variables de Permisos JS
$isAdmin = in_array($userRole, ['admin']);
$isMod = in_array($userRole, ['moderator']);
$isReadOnly = ($userStatus === 'banned_create');
$canCreate = !$isReadOnly; // Activos, Mods y Admins pueden crear
$currentUserId = $userId;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda de Eventos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .fade-in { animation: fadeIn 0.3s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden text-gray-800">

    <!-- LOADER GLOBAL -->
    <div id="globalLoader" class="fixed inset-0 bg-white/80 backdrop-blur-sm z-[9999] hidden flex-col items-center justify-center transition-opacity duration-300">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-200 border-t-indigo-600"></div>
        <span class="mt-3 text-indigo-600 font-bold text-xs tracking-widest uppercase">Cargando...</span>
    </div>

    <!-- NAVBAR -->
    <div class="shrink-0 bg-white shadow-sm z-20 relative">
        <?php include 'includes/navbar.php'; ?>
    </div>

    <!-- CONTENIDO -->
    <main class="flex-1 flex flex-col max-w-[1800px] mx-auto w-full py-6 px-4 min-h-0">
        
        <!-- HEADER -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 shrink-0 gap-4">
            <div class="flex items-center">
                <div class="bg-indigo-600 p-2.5 rounded-xl mr-4 text-white shadow-lg shadow-indigo-200">
                    <i class="ph-duotone ph-calendar-check text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Agenda</h1>
                    <p class="text-xs text-gray-500 font-medium">Gestión de eventos</p>
                </div>
            </div>
            
            <div class="flex items-center bg-white rounded-xl shadow-sm border border-gray-200 p-1.5">
                <button onclick="changePeriod(-1)" class="p-2 hover:bg-gray-50 rounded-lg text-gray-500 hover:text-indigo-600 transition" title="Semana Anterior"><i class="ph-bold ph-caret-left text-lg"></i></button>
                <div class="px-5 text-center min-w-[220px]">
                    <span id="periodLabel" class="block text-sm font-bold text-gray-800 capitalize tracking-wide">...</span>
                </div>
                <button onclick="changePeriod(1)" class="p-2 hover:bg-gray-50 rounded-lg text-gray-500 hover:text-indigo-600 transition" title="Semana Siguiente"><i class="ph-bold ph-caret-right text-lg"></i></button>
                <div class="h-6 w-px bg-gray-200 mx-2"></div>
                <button onclick="goToToday()" class="mr-1 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-4 py-2 rounded-lg transition border border-indigo-100 uppercase tracking-wide">Hoy</button>
            </div>

            <?php if($canCreate): ?>
            <button onclick="openModal()" class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl flex items-center shadow-lg transition transform hover:-translate-y-0.5 font-bold text-sm group">
                <i class="ph-bold ph-plus-circle mr-2 text-xl group-hover:scale-110 transition-transform"></i> Nuevo
            </button>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN PENDIENTES (Solo Admin/Mod) -->
        <?php if($isAdmin || $isMod): ?>
        <div id="pendingSection" class="hidden mb-6 shrink-0 fade-in">
            <div class="bg-amber-50 border border-amber-200/60 rounded-2xl p-3 shadow-sm flex items-center overflow-hidden relative">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-amber-400"></div>
                <div class="mr-4 px-4 py-2 border-r border-amber-200/60 text-center bg-amber-100/50 rounded-lg ml-2">
                    <i class="ph-duotone ph-warning-circle text-2xl text-amber-600 block mb-1"></i>
                    <span class="text-[10px] font-extrabold text-amber-700 uppercase tracking-wide">Por Aprobar</span>
                </div>
                <div class="flex-1 flex overflow-x-auto space-x-4 pb-2 pt-1 custom-scroll" id="pendingContainer"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRID PRINCIPAL (3 Columnas) -->
        <div class="flex-1 min-h-0 overflow-hidden flex flex-col">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
                
                <!-- PASADO -->
                <div class="flex flex-col h-full bg-gray-50/50 rounded-2xl border border-gray-200/60 overflow-hidden">
                    <div class="p-3 border-b border-gray-200 bg-gray-100/50 flex justify-between items-center shrink-0 backdrop-blur-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2"><i class="ph-bold ph-clock-counter-clockwise"></i> Semana Pasada</span>
                        <span id="datePast" class="text-[10px] bg-white px-2 py-1 rounded border border-gray-200 text-gray-400 font-mono font-medium">--</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scroll min-h-0" id="colPast"></div>
                </div>

                <!-- ACTUAL -->
                <div class="flex flex-col h-full bg-white rounded-2xl border-2 border-indigo-100 shadow-xl shadow-indigo-50/40 overflow-hidden relative z-10">
                    <div class="p-4 border-b border-indigo-50 bg-indigo-50/30 flex justify-between items-center shrink-0">
                        <span class="text-sm font-bold text-indigo-700 uppercase flex items-center tracking-wide">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2.5 animate-pulse shadow-sm shadow-indigo-400"></span> Semana Actual
                        </span>
                        <span id="dateCurrent" class="text-xs font-bold text-indigo-600 bg-white px-2.5 py-1 rounded-md border border-indigo-100 shadow-sm font-mono">--</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scroll min-h-0 bg-gradient-to-b from-white via-white to-indigo-50/20" id="colCurrent"></div>
                </div>

                <!-- FUTURO -->
                <div class="flex flex-col h-full bg-gray-50/50 rounded-2xl border border-gray-200/60 overflow-hidden">
                    <div class="p-3 border-b border-gray-200 bg-gray-100/50 flex justify-between items-center shrink-0 backdrop-blur-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2"><i class="ph-bold ph-calendar-plus"></i> Semana Siguiente</span>
                        <span id="dateNext" class="text-[10px] bg-white px-2 py-1 rounded border border-gray-200 text-gray-400 font-mono font-medium">--</span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scroll min-h-0" id="colNext"></div>
                </div>
            </div>
        </div>
    </main>

    <!-- INCLUIR MODAL -->
    <?php include 'includes/events_modal_partial.php'; ?>

    <script src="js/event_preview.js"></script>
    <script src="js/render_event_card_clean.js"></script>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // CONFIGURACIÓN API MODULAR
        const API_EVENTS = 'public/api/events.php';
        const API_TYPES  = 'public/api/types.php';
        const API_USERS  = 'public/api/users.php';

        const myId = <?php echo $currentUserId; ?>;
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const isMod = <?php echo $isMod ? 'true' : 'false'; ?>;
        const canCreate = <?php echo $canCreate ? 'true' : 'false'; ?>;

        // ESTADO GLOBAL
        let referenceDate = getMonday(new Date());
        let currentEventsList = []; 
        let globalUsers = [];

        // --- LOADER (mejorado: asegurar display flex centralizado) ---
        const Loader = {
            show: () => { const g = document.getElementById('globalLoader'); g.classList.remove('hidden'); g.classList.add('flex'); },
            hide: () => { const g = document.getElementById('globalLoader'); g.classList.add('hidden'); g.classList.remove('flex'); }
        };

        // --- HELPERS ---
        function hexToRgba(hex, alpha) {
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        function getMonday(d) {
            d = new Date(d);
            var day = d.getDay(), diff = d.getDate() - day + (day == 0 ? -6 : 1); 
            return new Date(d.setDate(diff));
        }
        function formatDateDB(d) { return d.toISOString().split('T')[0]; }
        function formatDatePretty(d) { return d.toLocaleDateString('es-ES', {day:'numeric', month:'short'}); }

            // --- RENDERIZADO VISUAL (IDÉNTICO A EVENT_TYPES.PHP) ---
        function renderCard(ev) {
            const evDate = new Date(ev.start || ev.event_date || Date.now());
            const dayNum = evDate.getDate();
            const monthName = evDate.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase().replace('.','');

            const mode = (ev.display_mode || 'block').toLowerCase();
            const color = ev.color || '#6B7280';
            const rawTitle = String(ev.title || 'Evento');
            const title = rawTitle.replace('⏳ ','').replace('🎂 ','');
            const icon = ev.icon || 'circle';
            const isBirthday = ev.type === 'birthday' || ev.type === 'birthday';

            const canEdit = !!ev.editable;
            const editBtn = canEdit ? `<button onclick="openEditModal(${JSON.stringify(ev.id)})" class="ml-2 text-gray-500 hover:text-indigo-600 p-1.5 bg-white rounded-lg transition shadow-sm border border-gray-100" title="Modificar"><i class="ph-bold ph-pencil-simple text-lg"></i></button>` : '';
            const authorBadge = ((isAdmin || isMod) && !isBirthday) ? `<div class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-gray-100 flex items-center"><i class="ph-bold ph-user mr-1"></i> ${ev.creator_name || 'Sistema'}</div>` : '';

            // Imagen base
            const hasImage = !!ev.image_url;
            const imgTag = hasImage ? `<img src="${ev.image_url}" class="object-cover">` : '';

            // Helper styles
            const badgeStyle = `background: ${hexToRgba(color, 0.08)}; color: ${color};`;

            // Render por modo
            let html = '';

            switch(mode) {
                case 'detailed':
                    html = `
                    <div class="fade-in mb-3 group bg-white rounded-lg shadow-sm border border-gray-100 relative overflow-hidden flex items-center gap-3 p-3" style="border-left:4px solid ${color};">
                        <div class="flex-shrink-0 w-12 h-12 rounded-${isBirthday?'full':'lg'} overflow-hidden">
                            ${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center text-white" style="background:${color}"><i class="ph-fill ph-${icon}"></i></div>` }
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-sm text-gray-800 truncate">${title}</h3>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-[10px] text-gray-500">${ev.type_name || ''}</span>
                                <span class="text-[10px] text-gray-400">${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</span>
                            </div>
                            ${ ev.description ? `<p class="text-xs text-gray-600 mt-2 line-clamp-2">${ev.description}</p>` : '' }
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'photo':
                    html = `
                    <div class="fade-in group flex items-center bg-white rounded-lg border border-gray-100 p-2 shadow-sm" style="border-left:3px solid ${color};">
                        <div class="w-10 h-10 overflow-hidden rounded-md flex-shrink-0">${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="ph-fill ph-image"></i></div>` }</div>
                        <div class="ml-3 flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-sm truncate">${title}</div>
                                <div class="text-[10px] text-gray-400">${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</div>
                            </div>
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'banner':
                    let img = '';
                    if (ev.image_url) {
                        img = `<div class="w-full h-24 bg-gray-100 flex items-center justify-center overflow-hidden"><img src="${ev.image_url}" class="object-cover w-full h-full"></div>`;
                    }
                    html = `
                    <div class="fade-in group shadow-sm bg-white overflow-hidden p-0 flex flex-col mb-3 cursor-pointer" onclick="showEventDetail(${ev.id})">
                        <div class="h-4 w-full flex items-center justify-between px-1 shadow-sm z-10" style="background-color:${color}">
                            <span class="text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1"><i class="ph-bold ph-${icon}"></i> ${ev.type_name || title}</span>
                        </div>
                        ${img}
                        <div class="p-3">
                            <div class="text-sm text-gray-700">${ev.description ? ev.description.substring(0,120) : ''}</div>
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'important':
                    html = `
                    <div class="fade-in group rounded-xl overflow-hidden relative mb-3">
                        ${ hasImage ? `<div class="h-36 w-full overflow-hidden">${imgTag}</div>` : `<div class="h-24 w-full" style="background:${color}"></div>` }
                        <div class="absolute left-4 bottom-4 text-white">
                            <h3 class="font-extrabold text-lg leading-tight">${title}</h3>
                            <div class="text-sm opacity-90">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</div>
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'badge':
                    html = `
                    <div class="fade-in group bg-white rounded-lg mb-3 shadow-sm border border-gray-100 w-full" style="border-left:4px solid ${color};">
                        <div class="p-3 flex items-center gap-3">
                            <span class="badge-responsive flex-shrink-0 flex items-center justify-center text-[12px] font-bold px-3 py-1 rounded-md" style="${badgeStyle}"><i class="ph-bold ph-${icon}"></i></span>
                            <div class="text-sm font-medium whitespace-normal">${title}</div>
                            ${editBtn}
                        </div>
                    </div>`;
                    break;

                case 'gradient':
                    html = `
                    <div class="fade-in group rounded-xl p-3 mb-3 text-white" style="background: linear-gradient(135deg, ${color}, #ffffff33);">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0">${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full" style="background:rgba(255,255,255,0.12)"></div>` }</div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-sm leading-tight">${title}</h3>
                                <div class="text-[10px] opacity-90">${ev.type_name || ''}</div>
                            </div>
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'subtle':
                    html = `
                    <div class="fade-in group bg-white rounded-xl p-3 mb-3 border border-gray-100" style="background:${hexToRgba(color, 0.06)}; border-left:4px solid ${color};">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" style="background:${color}"><i class="ph-fill ph-${icon}"></i></div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-sm truncate">${title}</h3>
                                <div class="text-[10px] text-gray-500">${ev.type_name || ''}</div>
                            </div>
                        </div>
                        ${editBtn}
                    </div>`;
                    break;

                case 'background':
                    html = `
                    <div class="fade-in group rounded-xl p-4 mb-3 text-center text-white" style="background:${color};">
                        <div class="text-lg font-extrabold">${title}</div>
                        <div class="text-xs opacity-90 mt-1">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {day:'numeric', month:'short'})}</div>
                        ${editBtn}
                    </div>`;
                    break;

                default:
                    // block / default
                    html = `
                    <div class="p-3 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition group relative flex flex-col mb-3 fade-in" style="background:#fff; border-left:4px solid ${color};">
                        ${ hasImage ? `<div class="h-24 w-full rounded-lg mb-2 overflow-hidden bg-gray-100"><img src="${ev.image_url}" class="w-full h-full object-cover"></div>` : '' }
                        <div class="flex gap-3 mb-1">
                            <div class="flex flex-col items-center justify-center bg-white border border-gray-100 rounded-lg w-10 h-12 shadow-sm shrink-0">
                                <span class="text-[9px] font-bold text-gray-400 uppercase leading-none mt-1">${monthName}</span>
                                <span class="text-xl font-bold text-gray-800 leading-none">${dayNum}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-sm leading-tight line-clamp-2">${title}</h3>
                                </div>
                                <div class="flex items-center mt-1 space-x-1">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider flex items-center gap-1" style="${badgeStyle}">
                                        <i class="ph-bold ph-${icon}"></i> ${ev.type_name || 'Evento'}
                                    </span>
                                </div>
                            </div>
                        </div>
                        ${ ev.description ? `<p class="text-xs opacity-80 mt-1 line-clamp-2">${ev.description}</p>` : '' }
                        ${editBtn}
                        ${authorBadge}
                    </div>`;
            }

            return html;
        }

        // --- RENDERIZADO PENDIENTES ---
        function renderPendingCard(ev) {
            return `
            <div class="min-w-[280px] bg-white p-3 rounded-xl shadow-sm border border-amber-200 flex flex-col group hover:shadow-md transition relative fade-in">
                <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-bold text-gray-800 text-sm line-clamp-1" title="${ev.title}">${ev.title.replace('⏳ ','')}</h3>
                        <p class="text-[10px] text-gray-500 flex items-center mt-1"><i class="ph-bold ph-user mr-1"></i> ${ev.creator_name}</p>
                        <p class="text-[10px] text-gray-400">${new Date(ev.start).toLocaleDateString()}</p>
                    </div>
                    <button onclick="openEditModal(${JSON.stringify(ev.id)})" class="text-gray-400 hover:text-indigo-600 bg-gray-50 p-1.5 rounded-lg transition"><i class="ph-bold ph-pencil-simple text-lg"></i></button>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-auto pt-2 border-t border-amber-100">
                    <button onclick="moderate(${JSON.stringify(ev.id)}, 'approved')" class="bg-green-50 hover:bg-green-100 text-green-700 text-[10px] font-bold py-1.5 rounded-lg border border-green-200 transition flex items-center justify-center gap-1"><i class="ph-bold ph-check"></i> APROBAR</button>
                    <button onclick="moderate(${JSON.stringify(ev.id)}, 'rejected')" class="bg-red-50 hover:bg-red-100 text-red-700 text-[10px] font-bold py-1.5 rounded-lg border border-red-200 transition flex items-center justify-center gap-1"><i class="ph-bold ph-x"></i> RECHAZAR</button>
                </div>
            </div>`;
        }

        // --- CARGA DE DATOS ---
        async function loadData() {
            Loader.show();
            // Calcular fechas (semana de referencia)
            const cw = { start: new Date(referenceDate), end: new Date(referenceDate) };
            cw.end.setDate(cw.end.getDate() + 6);

            const ps = new Date(cw.start); ps.setDate(ps.getDate()-7);
            const ne = new Date(cw.end); ne.setDate(ne.getDate()+7);

            // Labels
            const opts = { day: 'numeric', month: 'short' };
            const periodLabelEl = document.getElementById('periodLabel'); if(periodLabelEl) periodLabelEl.innerText = new Intl.DateTimeFormat('es-ES', { month: 'long', year: 'numeric' }).format(cw.start);
            const dp = document.getElementById('datePast'); const dc = document.getElementById('dateCurrent'); const dn = document.getElementById('dateNext');
            if(dp) dp.innerText = `${ps.toLocaleDateString('es-ES', opts)} - ${new Date(cw.start.getTime()-86400000).toLocaleDateString('es-ES', opts)}`;
            if(dc) dc.innerText = `${cw.start.toLocaleDateString('es-ES', opts)} - ${cw.end.toLocaleDateString('es-ES', opts)}`;
            if(dn) dn.innerText = `${new Date(cw.end.getTime()+86400000).toLocaleDateString('es-ES', opts)} - ${ne.toLocaleDateString('es-ES', opts)}`;

            // Cargar usuarios admin (si aplica)
            if(isAdmin && globalUsers.length === 0){
                try {
                    const uRes = await fetch(`${API_USERS}?action=get_users&limit=300`);
                    const uData = await uRes.json();
                    globalUsers = uData.data || [];
                } catch(e) { globalUsers = []; }
            }

            // Fetch eventos desde API
            const s = formatDateDB(ps), e = formatDateDB(ne);
            try {
                const res = await fetch(`${API_EVENTS}?action=get_all&context=management&start_date=${s}&end_date=${e}&limit=300`);
                const r = await res.json();
                currentEventsList = r.data || [];
                // Orden cronológico estable
                currentEventsList.sort((a,b) => {
                    if ((a.start||'') < (b.start||'')) return -1;
                    if ((a.start||'') > (b.start||'')) return 1;
                    const ao = (a.order||0) - (b.order||0);
                    if (ao !== 0) return ao;
                    return (a.title||'').localeCompare(b.title||'');
                });
            } catch(err) { console.error(err); currentEventsList = []; }

            // Limpiar columnas
            ['colPast','colCurrent','colNext'].forEach(id => { const el = document.getElementById(id); if(el) el.innerHTML = ''; });
            const pendingContainer = document.getElementById('pendingContainer'); if(pendingContainer) pendingContainer.innerHTML = '';

            let pCount = 0;
            if(currentEventsList.length === 0) {
                const cc = document.getElementById('colCurrent'); if(cc) cc.innerHTML = `<div class="flex flex-col items-center justify-center h-40 text-gray-400 opacity-50"><i class="ph-duotone ph-calendar-slash text-5xl mb-3"></i><span class="text-sm font-medium">Sin eventos</span></div>`;
            }

            // Renderizar eventos
            const sStr = formatDateDB(cw.start); const eStr = formatDateDB(cw.end);
            currentEventsList.forEach(ev => {
                const isPublic = ev.visibility === 'public';
                const isMine = ev.created_by == myId;
                const isHoliday = ev.type_slug === 'holiday' || ev.display_mode === 'background';

                if(!isAdmin && !isMod && !isMine && !isHoliday && !isPublic) return;

                // Pendientes
                if ((isAdmin || isMod) && ev.status === 'pending') {
                    if(pendingContainer) { pendingContainer.innerHTML += renderPendingCard(ev); pCount++; }
                    return;
                }

                const evDateStr = (ev.start||'').split('T')[0];
                const card = (typeof window.renderEventCard === 'function') ? window.renderEventCard(ev) : renderCard(ev);

                if (evDateStr < sStr) {
                    const el = document.getElementById('colPast'); if(el) el.innerHTML += card;
                } else if (evDateStr > eStr) {
                    const el = document.getElementById('colNext'); if(el) el.innerHTML += card;
                } else {
                    const el = document.getElementById('colCurrent'); if(el) el.innerHTML += card;
                }
            });

            // Mostrar/ocultar pendientes
            if(isAdmin || isMod) {
                const sec = document.getElementById('pendingSection'); if(sec) { if(pCount>0) sec.classList.remove('hidden'); else sec.classList.add('hidden'); }
            }

            Loader.hide();
        }

        // --- HELPERS ---
        function closeFormModal() { document.getElementById('formModal').classList.add('hidden'); }
        
        function openModal(){ 
            const formEl = document.getElementById('eventForm'); if(formEl) formEl.reset();
            const eIdEl = document.getElementById('eId'); if(eIdEl) eIdEl.value='';
            if(isAdmin) {
                const eCreatorEl = document.getElementById('eCreator'); if(eCreatorEl) eCreatorEl.value = myId;
                const sIn = document.getElementById('userSearchInput'); if(sIn) sIn.value = 'Yo';
            }
            const ip = document.getElementById('iPr'); if(ip) ip.classList.add('hidden');
            const btnDel = document.getElementById('btnDeleteModal'); if(btnDel) btnDel.classList.add('hidden');
            const modalEl = document.getElementById('formModal'); if(modalEl) modalEl.classList.remove('hidden');
        }

        // Toggle Imagen
        function tgImg(m){
            const bu=document.getElementById('bUp'), bur=document.getElementById('bUr'), tu=document.getElementById('tabUp'), tur=document.getElementById('tabUr');
            if(m==='up'){ bu.classList.remove('hidden'); bur.classList.add('hidden'); tu.classList.add('text-indigo-600','border-b-2','border-indigo-600'); tur.classList.remove('text-indigo-600','border-b-2'); }
            else { bu.classList.add('hidden'); bur.classList.remove('hidden'); tur.classList.add('text-indigo-600','border-b-2','border-indigo-600'); tu.classList.remove('text-indigo-600','border-b-2'); }
        }
        const eFileEl = document.getElementById('eFile');
        if (eFileEl) eFileEl.addEventListener('change', e=>{ if(e.target.files && e.target.files[0]) showP(URL.createObjectURL(e.target.files[0])); });
        const eUrlEl = document.getElementById('eUrl');
        if (eUrlEl) eUrlEl.addEventListener('input', e=>{ if(e.target.value.length>10) showP(e.target.value); });
        function showP(s){ const d = document.getElementById('iPr'); if(!d) return; const img = d.querySelector('img'); if(img) img.src = s; d.classList.remove('hidden'); }
        function clearImage(){ const d = document.getElementById('iPr'); if(d) d.classList.add('hidden'); const f = document.getElementById('eFile'); if(f) f.value=''; const u = document.getElementById('eUrl'); if(u) u.value=''; }

        // Actions
        async function moderate(id, status) {
            Swal.fire({ title: status==='approved'?'¿Aprobar?':'¿Rechazar?', showCancelButton: true, confirmButtonText: 'Sí' }).then(async r => {
                if(r.isConfirmed) {
                    await fetch(`${API_EVENTS}?action=moderate`,{method:'POST',body:JSON.stringify({id,status})});
                    loadData();
                }
            });
        }
        async function delEv(id) {
            Swal.fire({ title: '¿Eliminar?', text:'No se puede deshacer', icon:'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Eliminar' }).then(async r => {
                if(r.isConfirmed) {
                    await fetch(`${API_EVENTS}?action=delete`,{method:'POST',body:JSON.stringify({id})});
                    closeFormModal(); loadData();
                }
            });
        }

        // Navegación Fechas
        function changePeriod(d) { referenceDate.setDate(referenceDate.getDate()+(d*7)); loadData(); }
        function goToToday() { referenceDate = getMonday(new Date()); loadData(); }

        // Autocomplete Admin
        if(isAdmin){ 
            const sIn=document.getElementById('userSearchInput'), hIn=document.getElementById('eCreator'), dd=document.getElementById('userDropdown'), clr=document.getElementById('clearUserBtn');
            if(sIn){
                sIn.addEventListener('input',e=>{ const t=e.target.value.toLowerCase(); renderDD(globalUsers.filter(u=>u.nickname.toLowerCase().includes(t)||u.email.toLowerCase().includes(t))); dd.classList.remove('hidden'); });
                sIn.addEventListener('focus',()=>{if(sIn.value==='')renderDD(globalUsers); dd.classList.remove('hidden');});
                document.addEventListener('click',e=>{if(!sIn.contains(e.target)&&!dd.contains(e.target))dd.classList.add('hidden');});
                if(clr) clr.addEventListener('click',()=>{selectUser(myId,'Yo'); sIn.value=''; clr.classList.add('hidden');});
            }
            function renderDD(u){ if(u.length===0)return dd.innerHTML='<li class="p-3 text-xs text-gray-400">0 resultados</li>'; dd.innerHTML=u.map(x=>`<li onclick="selectUser(${x.id},'${x.nickname}')" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm font-medium border-b flex justify-between"><span>@${x.nickname}</span><span class="text-xs text-gray-400">${x.first_name}</span></li>`).join(''); }
            window.selectUser=(id,n)=>{ if(hIn)hIn.value=id; if(sIn)sIn.value=n; if(dd)dd.classList.add('hidden'); if(clr && id!=myId) clr.classList.remove('hidden'); };
        }

        // Init
        // --- MODERACIÓN (Solo Admin/Mod) ---
        async function moderate(id, action) {
            const result = await Swal.fire({
                title: action === 'approved' ? '¿Aprobar evento?' : '¿Rechazar evento?',
                text: action === 'approved' ? 'El evento será visible para todos los usuarios.' : 'El evento será rechazado y no se publicará.',
                icon: action === 'approved' ? 'success' : 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'approved' ? '#10B981' : '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: action === 'approved' ? 'Aprobar' : 'Rechazar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;

            try {
                const res = await fetch(`${API_EVENTS}?action=moderate`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, action })
                });
                const r = await res.json();
                if (r.success) {
                    await Swal.fire({
                        title: '¡Listo!',
                        text: action === 'approved' ? 'Evento aprobado correctamente.' : 'Evento rechazado.',
                        icon: 'success',
                        confirmButtonColor: '#4F46E5'
                    });
                    loadData();
                } else {
                    await Swal.fire({
                        title: 'Error',
                        text: r.message || 'No se pudo procesar la moderación.',
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                }
            } catch (e) {
                await Swal.fire({
                    title: 'Error',
                    text: 'Error de conexión con el servidor.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
        }

        // --- EDITAR EVENTO ---
        async function openEditModal(id) {
            try {
                const res = await fetch(`${API_EVENTS}?action=get&id=${id}`);
                const data = await res.json();
                if (data.success) {
                    const ev = data.data;
                    document.getElementById('eId').value = ev.id || '';
                    document.getElementById('eTitle').value = ev.title || '';
                    document.getElementById('eDate').value = ev.event_date || '';
                    document.getElementById('eEndDate').value = ev.end_date || '';
                    document.getElementById('eStartTime').value = ev.start_time || '';
                    document.getElementById('eEndTime').value = ev.end_time || '';
                    document.getElementById('eDesc').value = ev.description || '';
                    document.getElementById('eTypeSelect').value = ev.event_type_id || '';
                    document.getElementById('eColor').value = ev.color || '#4F46E5';
                    document.getElementById('eVis').value = ev.visibility || 'private';
                    if (isAdmin) document.getElementById('eCreator').value = ev.created_by || myId;
                    // Imagen
                    if (ev.image) {
                        // Mostrar imagen si existe
                        const imgEl = document.querySelector('#iPr img');
                        if (imgEl) imgEl.src = 'data:image/jpeg;base64,' + ev.image; // Asumiendo base64
                        document.getElementById('iPr').classList.remove('hidden');
                    } else {
                        document.getElementById('iPr').classList.add('hidden');
                    }
                    document.getElementById('formModal').classList.remove('hidden');
                    document.getElementById('modalTitle').innerText = 'Editar Evento';
                    document.getElementById('btnDeleteModal').classList.remove('hidden');
                } else {
                    await Swal.fire({
                        title: 'Error',
                        text: 'No se pudo cargar el evento.',
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                }
            } catch (e) {
                console.error('Error al cargar evento:', e);
                await Swal.fire({
                    title: 'Error',
                    text: 'Error de conexión.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
        }

        // --- SUBMIT FORM ---
        document.getElementById('eventForm').addEventListener('submit', async e => {
            e.preventDefault();
            console.log('Submit triggered'); // Debug

            // Validaciones
            const title = document.getElementById('eTitle').value.trim();
            if (!title) {
                await Swal.fire({
                    title: 'Campo requerido',
                    text: 'El título del evento es obligatorio.',
                    icon: 'warning',
                    confirmButtonColor: '#4F46E5'
                });
                return;
            }

            const date = document.getElementById('eDate').value;
            if (!date) {
                await Swal.fire({
                    title: 'Campo requerido',
                    text: 'La fecha de inicio es obligatoria.',
                    icon: 'warning',
                    confirmButtonColor: '#4F46E5'
                });
                return;
            }

            // Confirmación
            const result = await Swal.fire({
                title: '¿Guardar evento?',
                text: '¿Estás seguro de guardar los cambios en este evento?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4F46E5',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;

            // Validación de fechas
            const startD = document.getElementById('eDate').value;
            const endD = document.getElementById('eEndDate').value || startD;
            const startT = document.getElementById('eStartTime').value;
            const endT = document.getElementById('eEndTime').value;

            if (startD && endD) {
                const s = new Date(`${startD}T${startT || '00:00'}`);
                const e = new Date(`${endD}T${endT || '23:59'}`);
                if (e < s) {
                    await Swal.fire({
                        title: 'Error de validación',
                        text: 'La fecha de fin no puede ser anterior a la de inicio.',
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                    return;
                }
            }

            const fd = new FormData(e.target);
            const btn = e.submitter;
            btn.disabled = true;
            btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Guardando...';

            try {
                const res = await fetch(`${API_EVENTS}?action=save`, { method: 'POST', body: fd });
                const r = await res.json();
                if (r.success) {
                    await Swal.fire({
                        title: '¡Éxito!',
                        text: 'Evento guardado correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#4F46E5'
                    });
                    closeFormModal();
                    loadData();
                } else {
                    await Swal.fire({
                        title: 'Error',
                        text: r.message || 'No se pudo guardar el evento.',
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                }
            } catch (x) {
                await Swal.fire({
                    title: 'Error',
                    text: 'Error al guardar el evento.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> Guardar';
            }
        });

        async function init() {
            Loader.show();
            // Cargar tipos
            try {
                const res = await fetch(`${API_TYPES}?action=list`);
                const types = await res.json();
                const sel = document.getElementById('eTypeSelect');
                sel.innerHTML = types.filter(t => t.slug !== 'birthday').map(t => `<option value="${t.id}">${t.name}</option>`).join('');
            } catch(e){}
            // Cargar datos
            await loadData();
            Loader.hide();
        }

        init();
    </script>
</body>
</html>