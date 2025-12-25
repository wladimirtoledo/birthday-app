<?php
// index.php
require_once 'includes/functions.php';
$isGuest = !isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'moderator']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario - EventApp</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Iconos Phosphor -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- FullCalendar v6 -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/es.global.min.js'></script>
    <script src="js/event_preview.js"></script>
    <script src="js/render_event_card_clean.js"></script>
    <style>
        /* --- ESTILOS VISUALES --- */
        #calendar-container {
            border: 1px solid #E2E8F0; border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); background-color: white; padding: 4px;
        }
        
        /* Celdas y Estructura */
        .fc-theme-standard td, .fc-theme-standard th { border-color: #F1F5F9; }
        .fc-scrollgrid { border: none !important; }
        .fc-daygrid-day-frame { padding: 0 !important; min-height: 140px; display: flex; flex-direction: column; position: relative; overflow: visible; }
        .fc-daygrid-day-top { flex-direction: row; justify-content: flex-end; z-index: 30; position: relative; }
        .fc-daygrid-day-number { color: #64748b; font-weight: 800; font-size: 0.85rem; text-decoration: none !important; text-shadow: 0 1px 0 rgba(255,255,255,0.9); z-index: 30; }
        
        /* Día actual */
        .fc-day-today { background-color: #F8FAFC !important; }
        .fc-day-today .fc-daygrid-day-number { background-color: #4F46E5; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; text-shadow: none; }

        /* Eventos */
        .fc-daygrid-day-events { margin: 0 !important; padding-bottom: 2px; z-index: 20; display: block; }
        .fc-event { background: transparent !important; border: none !important; box-shadow: none !important; cursor: pointer; border-radius: 0 !important; overflow: visible !important; }
        .fc-event-main { width: 100%; overflow: visible; }
        .fc-daygrid-event-dot, .fc-event-time { display: none !important; }
            .fc-daygrid-dot-event { padding: 1px 0px; }
        .fc-bg-event { opacity: 1 !important; z-index: 1 !important; background: transparent !important; }

        /* Banner-specific: cuando un evento es 'banner' queremos que ocupe el tope del día */
        /*.fc-event.banner-mode { position: absolute !important; left: 0; right: 0; top: 0; z-index: 25; border-radius: 0 !important; }
        .fc-daygrid-day-frame .fc-event.banner-mode { margin: 0 !important; }*/

        /* Ajustes: colocar la cinta (banner) POR ENCIMA del recuadro del día
           y darle aspecto de título; scoped por #calendar para no afectar otras pantallas */
        #calendar .fc-daygrid-day-frame { position: relative; }

        /* Ubicar la cinta justo encima del contenedor .fc-daygrid-day-top (como encabezado).
           Usamos un offset positivo pequeño (no negativo) y reservamos más espacio en la celda. */
        #calendar .fc-event.banner-mode {
            position: absolute !important;
            left: 8px !important;
            right: 8px !important;
            top: 8px !important; /* mantener la cinta dentro del flujo visual pero sin tapar el número */
            z-index: 60 !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 6px 12px rgba(15,23,42,0.06);
            padding: 6px 10px !important;
            font-size: 0.92rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            background-clip: padding-box !important;
        }

        /* Altura mínima uniforme para celdas: usar min-height para no romper layout responsivo */
        #calendar { --fc-day-height: 130px; }
        #calendar .fc-daygrid-day-frame.fc-scrollgrid-sync-inner {
            min-height: 90px !important;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            /* padding-top eliminado para mayor compacidad */
            overflow: visible;
        }

        /* Número del día: scoped al calendario y más protagonista */
        #calendar .fc-daygrid-day-top { z-index: 40; position: relative; }
        #calendar .fc-daygrid-day-top { display: initial !important; padding: 0 !important; }
        #calendar .fc-daygrid-day-number {
            color: #111827; /* slate-900 */
            font-weight: 800;
            font-size: 1.02rem;
            margin-left: 6px;
            padding: 4px 6px;
            border-radius: 0.45rem;
            background: transparent;
        }

        /* Día actual: círculo más grande y visible */
        #calendar .fc-day-today .fc-daygrid-day-number {
            background-color: #4F46E5;
            color: white;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 900;
            font-size: 1rem;
            box-shadow: 0 6px 12px rgba(79,70,229,0.12);
        }

        /* Área de eventos: scroll dentro del espacio restante de la celda */
        #calendar .fc-daygrid-day-events {
            padding: 1px 1px !important;
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto;
            overflow: auto;
        }

        /* Compactar tarjetas SOLO dentro del calendario (mantener estilos fuente en event_types.php) */
        #calendar .fc-event .fade-in,
        #calendar .fc-event .group,
        #calendar .fc-event > div {
            padding: 8px !important;
            font-size: 13px !important;
            border-radius: 0.45rem !important;
        }

        /* Reducir imágenes/avatares dentro de las tarjetas del calendario */
        #calendar .fc-event img { max-height:44px !important; max-width:44px !important; height:44px !important; width:44px !important; object-fit:cover !important; }

        /* Badges dentro del calendario: ocupar todo el ancho y ser legibles */
        #calendar .fc-event .badge-responsive { padding: 6px 8px !important; font-size: 12px !important; display: block !important; width: calc(100% - 4px) !important; box-sizing: border-box !important; }

        /* Evitar que la cinta empuje o tape el área de eventos: si existe la cinta ajustamos margen superior */
        #calendar .fc-daygrid-day-frame .fc-event.banner-mode + .fc-daygrid-day-events,
        #calendar .fc-daygrid-day-frame .banner-mode ~ .fc-daygrid-day-events { margin-top: 6px !important; }

        /* Badge adjustments */
        .fc-event .badge-inline { display: inline-flex; align-items: center; gap: 6px; max-width: 100%; white-space: normal; }

        /* UI */
        .fc-col-header-cell-cushion { color: #4F46E5; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 12px 0; }
        .fc-header-toolbar { display: none !important; }
        .custom-scroll::-webkit-scrollbar { width: 5px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col font-sans overflow-hidden text-slate-800">

    <div class="shrink-0 z-30 shadow-sm bg-white"><?php include 'includes/navbar.php'; ?></div>

    <main class="flex-1 container mx-auto px-4 py-6 flex flex-col min-h-0 overflow-hidden">
        
        <!-- HEADER CALENDARIO -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 shrink-0 gap-4">
            
            <div class="flex items-center gap-4 bg-white p-2 rounded-2xl shadow-sm border border-gray-200">
                <button id="prevBtn" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 transition"><i class="ph-bold ph-caret-left"></i></button>
                <h2 id="calendarTitle" class="text-xl font-bold text-gray-800 capitalize min-w-[200px] text-center">Cargando...</h2>
                <button id="nextBtn" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 transition"><i class="ph-bold ph-caret-right"></i></button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <button id="todayBtn" class="text-xs font-bold text-indigo-600 bg-indigo-50 px-4 py-1.5 rounded-lg hover:bg-indigo-100 transition border border-indigo-100">HOY</button>
            </div>

            <?php if($isGuest): ?>
                <a href="login.php" class="flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg text-sm">Iniciar Sesión</a>
            <?php endif; ?>
        </div>

        <!-- CONTENEDOR CALENDARIO -->
        <div id="calendar-container" class="flex-1 overflow-hidden flex flex-col relative z-10">
            <div id='calendar' class="flex-1 h-full"></div>
        </div>
    </main>

    <div class="shrink-0 bg-white border-t border-gray-200 z-30"><?php include 'includes/footer.php'; ?></div>

    <!-- MODAL DETALLE (LECTURA) -->
    <div id="eventModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform scale-100 transition-all duration-200">
            <div id="modalImageContainer" class="h-48 bg-gray-200 w-full hidden relative group">
                <img id="modalImage" src="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                <button onclick="closeModal()" class="absolute top-3 right-3 bg-black/30 hover:bg-black/50 text-white rounded-full p-1.5 transition backdrop-blur-md z-20"><i class="ph-bold ph-x text-lg"></i></button>
            </div>
            <div id="modalHeaderNoImg" class="px-6 py-4 border-b flex justify-between items-center hidden bg-gray-50">
                <h3 class="font-bold text-gray-500 text-xs uppercase tracking-wide">Detalles</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-1 hover:bg-gray-200 rounded-full transition"><i class="ph-bold ph-x text-lg"></i></button>
            </div>
            <div class="p-6 overflow-y-auto">
                <div class="flex gap-4 mb-5">
                    <div class="bg-indigo-50 border border-indigo-100 rounded-xl w-14 h-16 flex flex-col items-center justify-center shrink-0 shadow-sm text-indigo-700">
                        <span class="text-[10px] font-bold uppercase tracking-wider" id="modalMonth">MES</span>
                        <span class="text-2xl font-black leading-none" id="modalDay">00</span>
                    </div>
                    <div>
                        <span id="modalBadge" class="badge-responsive inline-flex text-[10px] font-bold px-2 py-1 rounded-lg bg-gray-100 text-gray-500 uppercase tracking-wide border border-gray-200 items-center gap-1 mb-1"><i id="modalIcon"></i> <span id="modalType">Tipo</span></span>
                        <h2 class="text-lg font-bold text-gray-900 leading-snug line-clamp-2" id="modalTitle">Título</h2>
                        <!-- Rango horario si existe -->
                        <p id="modalTimeRange" class="text-xs text-gray-500 mt-1 font-medium hidden"></p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <i class="ph-duotone ph-text-align-left mr-3 mt-0.5 text-lg text-indigo-400 shrink-0"></i>
                        <p id="modalDesc" class="leading-relaxed text-sm">...</p>
                    </div>
                    <div class="flex items-center text-xs text-gray-400 border-t border-gray-100 pt-3">
                        <span class="font-medium mr-1">Organizado por:</span> <span class="font-bold text-gray-600" id="modalCreator">Sistema</span>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button onclick="closeModal()" class="flex-1 py-2.5 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition">Cerrar</button>
                    <button id="modalActionBtn" class="flex-1 py-2.5 text-white font-bold rounded-xl transition shadow-lg hidden"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL EDICIÓN (FORMULARIO COMPLETO) -->
    <div id="formModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col transform scale-100 transition-transform">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center shrink-0">
                <h2 class="text-xl font-bold text-gray-800">Editar Evento</h2>
                <button onclick="closeFormModal()" class="text-gray-400 hover:text-gray-600"><i class="ph ph-x text-2xl"></i></button>
            </div>
            <div class="flex-1 overflow-y-auto p-6" style="scrollbar-width:thin;">
                <form id="eventForm" class="space-y-5">
                    <input type="hidden" name="id" id="eId">
                    
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Título</label><input type="text" name="title" id="eTitle" required class="w-full border p-2.5 rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 outline-none"></div>
                    
                    <!-- FECHAS Y HORAS -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Inicio</label>
                            <div class="flex gap-1">
                                <input type="date" name="date" id="eDate" required class="w-full border p-2 rounded text-sm">
                                <input type="time" name="start_time" id="eStartTime" class="w-20 border p-2 rounded text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Fin (Opcional)</label>
                            <div class="flex gap-1">
                                <input type="date" name="end_date" id="eEndDate" class="w-full border p-2 rounded text-sm">
                                <input type="time" name="end_time" id="eEndTime" class="w-20 border p-2 rounded text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                         <div><label class="block text-sm font-bold text-gray-700 mb-1">Tipo</label><select name="event_type_id" id="eTypeSelect" class="w-full border p-2.5 rounded-lg bg-white"></select></div>
                         <div><label class="block text-sm font-bold text-gray-700 mb-1">Visibilidad</label><select name="visibility" id="eVis" class="w-full border p-2.5 rounded-lg bg-white"><option value="private">Privado</option><option value="public">Público</option></select></div>
                    </div>
                    
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Color Manual</label><input type="color" name="color" id="eColor" class="h-10 w-full rounded cursor-pointer border-0 p-0 shadow-sm"></div>
                    
                    <div><label class="block text-sm font-bold text-gray-700 mb-1">Descripción</label><textarea name="description" id="eDesc" rows="3" class="w-full border p-2.5 rounded-lg border-gray-300"></textarea></div>
                </form>
            </div>
            <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                <button onclick="closeFormModal()" class="px-5 py-2.5 bg-white border font-bold rounded-xl">Cancelar</button>
                <button type="submit" form="eventForm" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-md transition transform hover:-translate-y-0.5">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        function hexToRgba(hex, alpha) {
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        // Cargar tipos (usamos endpoint directo `public/api/types.php?action=list`)
        async function loadTypes() {
            try {
                const res = await fetch('public/api/types.php?action=list');
                const types = await res.json();
                // cache global para consistencia visual en el calendario
                window.typesCache = Array.isArray(types) ? types : [];
                const sel = document.getElementById('eTypeSelect');
                if (sel && Array.isArray(types)) sel.innerHTML = types.filter(t => t.slug !== 'birthday').map(t => `<option value="${t.id}">${t.name}</option>`).join('');
                return window.typesCache;
            } catch(e){}
        }

        document.addEventListener('DOMContentLoaded', async function() {
            var calendarEl = document.getElementById('calendar');
            const today = new Date(); const diff = today.getDate() - today.getDay() - 6; const startDate = new Date(today.setDate(diff));

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridFourWeeks',
                views: { dayGridFourWeeks: { type: 'dayGrid', duration: { weeks: 4 } } },
                initialDate: startDate, locale: 'es', firstDay: 1, headerToolbar: false, height: '100%', dayMaxEvents: 3, eventOrder: 'order',

                datesSet: function(info) {
                    const title = info.view.title;
                    document.getElementById('calendarTitle').innerText = title.charAt(0).toUpperCase() + title.slice(1);
                },

                // 1. CARGA DE DATOS
                events: async function(info, successCallback, failureCallback) {
                    try {
                        const response = await fetch(`public/api/events.php?action=get_all&context=calendar&start_date=${info.startStr}&end_date=${info.endStr}`);
                        const data = await response.json();
                        console.log('EVENTOS RECIBIDOS:', data);
                        const events = (Array.isArray(data)?data:(data.data||[])).map(ev => {
                            // Respetar display_mode del tipo (fallback a 'block')
                            const mode = ev.display_mode || ev.type || 'block';
                            const display = (mode === 'background') ? 'background' : 'auto';
                            return { 
                                id: ev.id,
                                title: ev.title,
                                start: ev.start || ev.event_date,
                                allDay: !!ev.allDay,
                                display: display,
                                backgroundColor: ev.color,
                                borderColor: ev.color,
                                extendedProps: Object.assign({ mode: mode, icon: ev.icon||'circle', type_name: ev.type_name, type_color: ev.type_color||ev.color, age: ev.age, image_url: ev.image_url }, ev)
                            };
                        });
                        console.log('EVENTOS PROCESADOS:', events);
                        successCallback(events);
                    } catch (error) { console.error('ERROR AL CARGAR EVENTOS:', error); failureCallback(); }
                },

                // 2. RENDERIZADO VISUAL
                eventContent: function(arg) {
                    const props = arg.event.extendedProps || {};
                    const ev = Object.assign({}, props, {
                        title: arg.event.title,
                        start: arg.event.startStr,
                        event_date: arg.event.startStr ? arg.event.startStr.split('T')[0] : (props.event_date || ''),
                        id: arg.event.id,
                        display_mode: props.mode || 'block',
                        color: arg.event.backgroundColor,
                        icon: props.icon || 'circle',
                        type_name: props.type_name,
                        image_url: props.image_url,
                        description: props.description,
                        age: props.age,
                        isCalendar: true
                    });
                    // Usar siempre el renderizador unificado
                    if (typeof window.getCalendarCellHTML === 'function') {
                        const calMode = (ev.display_mode && ['badge','banner','background'].includes(ev.display_mode)) ? ev.display_mode : 'default';
                        const cardMode = ev.card_view || ev.card_mode || ev.cardStyle || (ev.display_mode && !['badge','banner','background'].includes(ev.display_mode) ? ev.display_mode : 'block');
                        const name = ev.type_name || ev.title || '';
                        const color = ev.color || ev.type_color || arg.event.backgroundColor || '#6B7280';
                        const icon = ev.icon || 'circle';
                        const html = window.getCalendarCellHTML(calMode, cardMode, color, icon, name, ev) || '';
                        return { html };
                    }
                    return { html: '' };
                },

                // 3. BACKGROUND
                eventDidMount: function(info) {
                    const props = info.event.extendedProps || {};
                    // BACKGROUND mode (full cell background)
                    if (props.mode === 'background' || info.event.display === 'background') {
                        const color = info.event.backgroundColor || '#666';
                        const icon = props.icon || 'circle';
                        const bgRgba = hexToRgba(color, 0.12);
                        info.el.innerHTML = `<div class="w-full h-full relative overflow-hidden" style="background-color: ${bgRgba};"><div class="absolute inset-0 flex items-center justify-center opacity-15 pointer-events-none"><i class="ph ph-${icon} transform -rotate-12" style="font-size: 5rem; color: ${color};"></i></div><div class="absolute bottom-1 right-1 text-[9px] font-bold uppercase opacity-60 pointer-events-none" style="color: ${color}; padding: 2px 4px;">${props.type_name || 'Feriado'}</div></div>`;
                        info.el.style.opacity = '1'; info.el.style.pointerEvents = 'none';
                        return;
                    }

                    // BANNER mode: insert banner as first child of .fc-daygrid-day-frame (just after <td>, before day number)
                    if (props.mode === 'banner') {
                        // Si el banner está envuelto en <a>, mantenerlo y solo moverlo
                        if (info.el.tagName === 'A') {
                            info.el.classList.add('cursor-pointer');
                        } else {
                            // Si no, envolver en <a> para click
                            const wrapper = document.createElement('a');
                            wrapper.className = 'cursor-pointer';
                            wrapper.innerHTML = info.el.outerHTML;
                            info.el.replaceWith(wrapper);
                            info.el = wrapper;
                        }
                        // Insertar como primer hijo del frame
                        const frame = info.el.closest('.fc-daygrid-day-frame');
                        if (frame) {
                            frame.insertBefore(info.el, frame.firstChild);
                            frame.style.paddingTop = '28px';
                        }
                        return;
                    }

                    // BADGE mode: insert just after the day number, not covering it
                    if (props.mode === 'badge') {
                        const inner = info.el.querySelector('div');
                        if (inner) inner.classList.add('badge-inline');
                        const dayCell = info.el.closest('.fc-daygrid-day-frame');
                        const dayTop = dayCell ? dayCell.querySelector('.fc-daygrid-day-top') : null;
                        if (dayCell && dayTop) {
                            // Insert badge right after the day number
                            if (dayTop.nextSibling) {
                                dayCell.insertBefore(info.el, dayTop.nextSibling);
                            } else {
                                dayCell.appendChild(info.el);
                            }
                        }
                        return;
                    }

                    // BADGE adjustments: ensure pills use inline class
                    if (props.mode === 'badge') {
                        const inner = info.el.querySelector('div');
                        if (inner) inner.classList.add('badge-inline');
                    }
                },

                // 4. CLICK DETALLES
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const dateObj = info.event.start;
                    document.getElementById('modalTitle').innerText = info.event.title.replace('⏳ ','').replace('🎂 ','');
                    document.getElementById('modalDesc').innerText = props.description || 'Sin descripción.';
                    document.getElementById('modalCreator').innerText = props.creator_name || 'Sistema';
                    const months = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
                    document.getElementById('modalMonth').innerText = months[dateObj.getMonth()];
                    document.getElementById('modalDay').innerText = dateObj.getDate();
                    
                    const badge = document.getElementById('modalBadge');
                    badge.innerHTML = `<i class="ph-bold ph-${props.icon||'circle'} text-sm mr-1"></i> ${props.type_name || (props.type==='birthday'?'Cumpleaños':'Evento')}`;
                    const c = info.event.backgroundColor || '#666';
                    badge.style.backgroundColor = c + '20'; badge.style.color = c; badge.style.borderColor = c + '40';
                    
                    // Mostrar rango de horas si existe
                    const timeRange = document.getElementById('modalTimeRange');
                    if (props.raw_start_time) {
                        let timeStr = props.raw_start_time.substring(0,5);
                        if (props.raw_end_time) timeStr += ' - ' + props.raw_end_time.substring(0,5);
                        timeRange.innerText = timeStr;
                        timeRange.classList.remove('hidden');
                    } else {
                        timeRange.classList.add('hidden');
                    }

                    const imgCont = document.getElementById('modalImageContainer');
                    const noImgHeader = document.getElementById('modalHeaderNoImg');
                    if (props.image_url) { document.getElementById('modalImage').src = props.image_url; imgCont.classList.remove('hidden'); noImgHeader.classList.add('hidden'); } 
                    else { imgCont.classList.add('hidden'); noImgHeader.classList.remove('hidden'); }

                    const btn = document.getElementById('modalActionBtn');
                    if (String(info.event.id).startsWith('usr_bday_')) {
                        btn.className = "flex-1 py-2.5 bg-pink-500 hover:bg-pink-600 text-white font-bold rounded-xl transition shadow-lg flex items-center justify-center gap-2";
                        btn.innerHTML = '<i class="ph-bold ph-user-gear"></i> Gestionar Usuario';
                        btn.onclick = function() { window.location.href = 'users.php'; };
                        btn.classList.remove('hidden');
                    } 
                    else if (props.editable) {
                        btn.className = "flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-lg flex items-center justify-center gap-2";
                        btn.innerHTML = '<i class="ph-bold ph-pencil-simple"></i> Editar Evento';
                        
                        // Guardar datos para editar
                        currentEventData = {
                            id: info.event.id,
                            title: info.event.title.replace('⏳ ','').replace('🎂 ',''),
                            event_date: info.event.startStr.split('T')[0],
                            // Datos raw desde API
                            raw_end_date: props.raw_end_date,
                            raw_start_time: props.raw_start_time,
                            raw_end_time: props.raw_end_time,
                            event_type_id: props.event_type_id || '',
                            visibility: props.visibility,
                            color: props.color,
                            description: props.description,
                            created_by: props.created_by,
                            creator_name: props.creator_name,
                            image_url: props.image_url
                        };
                        
                        btn.onclick = openFormModal;
                        btn.classList.remove('hidden');
                    } else {
                        btn.classList.add('hidden');
                    }
                    document.getElementById('eventModal').classList.remove('hidden');
                }
            });

            calendar.render();

            document.getElementById('prevBtn').addEventListener('click', () => calendar.prev());
            document.getElementById('nextBtn').addEventListener('click', () => calendar.next());
            document.getElementById('todayBtn').addEventListener('click', () => calendar.today());
            // precargar tipos para consistencia visual
            await loadTypes();
        });

        function closeModal() { document.getElementById('eventModal').classList.add('hidden'); }
        function closeFormModal() { document.getElementById('formModal').classList.add('hidden'); }
        
        function openFormModal() {
            closeModal();
            if(!currentEventData) return;
            
            document.getElementById('eId').value = currentEventData.id;
            document.getElementById('eTitle').value = currentEventData.title;
            
            // Usar datos raw para los inputs
            document.getElementById('eDate').value = currentEventData.event_date;
            document.getElementById('eEndDate').value = currentEventData.raw_end_date || currentEventData.event_date;
            
            const sTime = currentEventData.raw_start_time ? currentEventData.raw_start_time.substring(0,5) : '';
            const eTime = currentEventData.raw_end_time ? currentEventData.raw_end_time.substring(0,5) : '';
            
            document.getElementById('eStartTime').value = sTime;
            document.getElementById('eEndTime').value = eTime;

            document.getElementById('eTypeSelect').value = currentEventData.event_type_id || '';
            document.getElementById('eVis').value = currentEventData.visibility;
            document.getElementById('eColor').value = currentEventData.color;
            document.getElementById('eDesc').value = currentEventData.description || '';
            document.getElementById('formModal').classList.remove('hidden');
        }

        // Validación de Fechas al Guardar
        document.getElementById('eventForm').addEventListener('submit', async e => {
            e.preventDefault();
            const result = await Swal.fire({
                title: '¿Guardar cambios?',
                text: '¿Estás seguro de guardar los cambios en este evento?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4F46E5',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
            const startD = document.getElementById('eDate').value;
            const endD = document.getElementById('eEndDate').value || startD;
            const startT = document.getElementById('eStartTime').value;
            const endT = document.getElementById('eEndTime').value;

            if (startD && endD) {
                const s = new Date(`${startD}T${startT||'00:00'}`);
                const e = new Date(`${endD}T${endT||'23:59'}`);
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
            try {
                const res = await fetch('public/api/events.php?action=save', { method: 'POST', body: fd });
                const r = await res.json();
                if(r.success) { 
                    await Swal.fire({
                        title: '¡Éxito!',
                        text: 'Evento actualizado correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#4F46E5'
                    });
                    closeFormModal(); location.reload(); 
                }
                else {
                    await Swal.fire({
                        title: 'Error',
                        text: r.message || 'No se pudo guardar el evento.',
                        icon: 'error',
                        confirmButtonColor: '#4F46E5'
                    });
                }
            } catch(x) { 
                await Swal.fire({
                    title: 'Error',
                    text: 'Error al guardar el evento.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
        });
        
        document.getElementById('eventModal').addEventListener('click', (e) => { if (e.target.id === 'eventModal') closeModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    </script>
</body>
</html>