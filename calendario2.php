<?php
// calendario2.php: Calendario con celdas idénticas a la preview de event_types.php
require_once 'includes/functions.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="js/event_preview.js"></script>
    <style>
        .fancy-scroll { overflow-y: auto; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .fancy-scroll::-webkit-scrollbar { width: 6px; }
        .fancy-scroll::-webkit-scrollbar-track { background: transparent; }
        .fancy-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; border: 2px solid transparent; background-clip: content-box; }
        .fancy-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
    </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">
    <div class="shrink-0 bg-white shadow-sm z-20 relative">
        <?php include 'includes/navbar.php'; ?>
    </div>
    <main class="flex-1 max-w-5xl mx-auto w-full py-2 px-0 min-h-0 flex flex-col">
        <h1 class="text-2xl font-bold text-gray-900 mb-4 flex items-center"><i class="ph ph-calendar-blank mr-3 text-indigo-600 bg-indigo-50 p-2 rounded-lg"></i> Calendario Preview</h1>
        <div id="calendarPreview" class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex-1 flex flex-col p-0 m-0"></div>
    </main>
    <?php
    require_once 'config/db.php';
    $db = new Database();
    $pdo = $db->getConnection();
    $start = '2025-12-15';
    $end = date('Y-m-d', strtotime($start.' +27 days'));
    $eventos = [];
    $sql = "SELECT eve.title, eve.color, eve.event_date, tev.slug as type_slug, tev.display_mode, tev.icon FROM events eve, event_types tev where eve.event_type_id = tev.id AND eve.event_date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $eventos[] = $row;
    }
    ?>
    <script>
    const eventos = <?php echo json_encode($eventos); ?>;
    renderCalendarPreview(eventos);

    // Clon literal de renderUnifiedPreview de event_types.php para cada celda
    function renderCellPreview(day, dayEvents) {
        // Determinar modo especial
        const special = dayEvents.find(e => e.display_mode === 'background' || e.display_mode === 'badge' || e.display_mode === 'banner' || e.type_slug === 'holiday');
        let contClass = 'bg-white relative flex flex-col overflow-hidden z-10 shadow-2xl ring-4 ring-indigo-50/50 transition-all duration-300 min-h-[110px] min-w-[0] aspect-square border border-gray-100';
        if (special && (special.display_mode === 'background' || special.type_slug === 'holiday')) {
            contClass += ' ring-2 ring-red-200';
        } else {
            contClass += ' hover:ring-2 hover:ring-indigo-100';
        }

        // Header y eventos normales (siempre presentes)
        let headerHtml = `<div class='flex justify-between items-start'>
            <span class='text-[8px] font-bold text-gray-400 uppercase'>${day.dow}</span>
            <span class='text-sm font-black text-gray-800'>${day.num}</span>
        </div>`;
        const eventCards = dayEvents.filter(e => e.display_mode !== 'badge' && e.display_mode !== 'banner' && e.display_mode !== 'background' && e.type_slug !== 'holiday');
        const cardsHtml = eventCards.map(ev => {
            const mode = ev.display_mode || 'block';
            const color = ev.color || '#4F46E5';
            const icon = ev.icon || 'calendar-blank';
            const name = ev.title || 'Tipo';
            return window.getCardHTML ? window.getCardHTML(mode, color, icon, name, {...ev, start: day.iso}) : '';
        }).join('');

        // Badge
        const badge = dayEvents.find(e => e.display_mode === 'badge');
        let badgeHtml = '';
        if (badge) {
            badgeHtml = `<div class="w-fit max-w-full py-0.5 px-2 rounded-full mb-1 text-[7px] font-bold text-white flex items-center gap-1 shadow-sm" style="background-color:${badge.color||'#4F46E5'}">
                <i class="ph-fill ph-${badge.icon||'calendar-blank'}"></i> ${badge.title||'Tipo'}
            </div>`;
        }
        // Banner
        const banner = dayEvents.find(e => e.display_mode === 'banner');
        let bannerHtml = '';
        if (banner) {
            bannerHtml = `<div class="h-4 w-full flex items-center justify-between px-1 shadow-sm z-10 mb-1" style="background-color:${banner.color||'#4F46E5'}">
                <span class="text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1"><i class="ph-bold ph-${banner.icon||'calendar-blank'}"></i> ${banner.title||'Tipo'}</span>
            </div>`;
        }
        // Fondo completo (background/feriado)
        const feriado = dayEvents.find(e => e.display_mode === 'background' || e.type_slug === 'holiday');
        let feriadoBg = '';
        let feriadoOverlay = '';
        if (feriado) {
            const color = feriado.color || '#EF4444';
            const icon = feriado.icon || 'calendar-blank';
            const name = feriado.title || 'FERIADO';
            const bgRgba = window.hexToRgba ? window.hexToRgba(color, 0.15) : color;
            feriadoBg = `background-color: ${bgRgba};`;
            feriadoOverlay = `<div class="absolute inset-0 flex items-center justify-center opacity-20 pointer-events-none z-0"><i class="ph ph-${icon} text-4xl transform -rotate-12" style="color:${color}"></i></div>
                <div class="absolute bottom-1 right-1 text-[6px] font-bold uppercase opacity-80 pointer-events-none z-0" style="color: ${color}">${name}</div>`;
        }

        return `<div class='${contClass} relative' style='${feriadoBg}'>
            <div class='w-full h-full p-2 flex flex-col justify-between bg-white/80 relative z-10'>
                ${bannerHtml}
                <div class="flex justify-between items-start mb-1">${headerHtml}</div>
                ${badgeHtml}
                <div class='flex flex-col gap-0.5'>
                    ${cardsHtml}
                    <div class='w-3/4 h-1 bg-gray-100 rounded opacity-50'></div>
                </div>
            </div>
            ${feriadoOverlay}
        </div>`;
    }

    function renderCalendarPreview(events) {
        // Ajustar para que el primer día de la semana sea lunes y los días estén alineados correctamente
        const startDate = new Date('2025-12-15');
        const dayOfWeek = (startDate.getDay() + 6) % 7; // 0=lunes, 6=domingo
        const calendarStart = new Date(startDate);
        calendarStart.setDate(startDate.getDate() - dayOfWeek);
        const days = Array.from({length: 28}, (_,i) => {
            const d = new Date(calendarStart);
            d.setDate(calendarStart.getDate() + i);
            return {
                num: d.getDate(),
                dow: d.toLocaleDateString('es-ES', { weekday: 'short' }).toUpperCase().replace('.',''),
                iso: d.toISOString().slice(0,10)
            };
        });
        const container = document.getElementById('calendarPreview');
        const weekDays = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
        container.innerHTML = `
        <div class='w-full max-w-5xl bg-white shadow-lg overflow-hidden border border-gray-200'>
            <div class='grid grid-cols-7 bg-gray-50 border-b border-gray-200'>
                ${weekDays.map(day => `<div class='text-center py-2 font-bold text-gray-500 text-[15px] uppercase'>${day}</div>`).join('')}
            </div>
            <div class='aspect-[7/4] grid grid-cols-7 grid-rows-4 gap-px text-[16px] text-gray-700 mx-auto p-0'>
            ${days.map((d,i)=>{
                const dayEvents = events.filter(e => e.event_date === d.iso);
                return renderCellPreview(d, dayEvents);
            }).join('')}
            </div>
        </div>`;
    }
    </script>
</body>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
