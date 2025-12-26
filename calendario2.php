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
            /* Encapsulado de tarjetas de evento para evitar contaminación de estilos */
            .event-card-preview {
                border-radius: 0.5rem;
                box-shadow: 0 1px 4px 0 rgba(0,0,0,0.04);
                background: #fff;
                margin-bottom: 2px;
                padding: 2px 4px;
                min-width: 0;
                position: relative;
                z-index: 1;
            }
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
    $sql = "SELECT eve.title, eve.color, eve.event_date, eve.image, tev.slug as type_slug, tev.display_mode, tev.icon FROM events eve, event_types tev where eve.event_type_id = tev.id AND eve.event_date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Si hay imagen, convertirla a base64 para usar como image_url
        if (!empty($row['image'])) {
            $row['image_url'] = 'data:image/jpeg;base64,' . base64_encode($row['image']);
        } else {
            $row['image_url'] = null;
        }
        unset($row['image']);
        $eventos[] = $row;
    }
    ?>
    <script>
    const eventos = <?php echo json_encode($eventos); ?>;
    renderCalendarPreview(eventos);

    // Clon literal de renderUnifiedPreview de event_types.php para cada celda
    function renderCellPreview(day, dayEvents) {
        // --- Agrupación y estructura preview ---
        const special = dayEvents.find(e => e.display_mode === 'background' || e.display_mode === 'badge' || e.display_mode === 'banner' || e.type_slug === 'holiday');
        const feriado = dayEvents.find(e => e.display_mode === 'background' || e.type_slug === 'holiday');
        let contClass = 'relative flex flex-col overflow-hidden z-10 transition-all duration-300 min-h-[110px] min-w-[0] aspect-square';
        let style = '';
        if (feriado) {
            const color = feriado.color || '#EF4444';
            contClass += ' ring-2 ring-red-200';
            style = `background-color: ${window.hexToRgba ? window.hexToRgba(color, 0.15) : color};`;
        } else {
            contClass += ' bg-white shadow-2xl ring-4 ring-indigo-50/50 hover:ring-2 hover:ring-indigo-100';
            style = '';
        }

        // Agrupar eventos por tipo
        const banner = dayEvents.find(e => e.display_mode === 'banner');
        const badge = dayEvents.find(e => e.display_mode === 'badge');
        const normalEvents = dayEvents.filter(e => !['badge','banner','background'].includes(e.display_mode) && e.type_slug !== 'holiday');

        // --- ZONA ARRIBA: Banner, header, badge ---
        let headerZone = '';
        if (banner) {
            headerZone += `<div class=\"h-4 w-full flex items-center justify-between px-1 shadow-sm z-10\" style=\"background-color:${banner.color||'#4F46E5'}\"><span class=\"text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1\"><i class=\"ph-bold ph-${banner.icon||'calendar-blank'}\"></i> ${banner.title||'Tipo'}</span></div>`;
        }
        headerZone += `<div class=\"flex justify-between items-start mb-1\" style=\"min-height:22px; background:${feriado ? 'transparent' : '#fff'};\"><span class=\"text-[8px] font-bold ${feriado ? 'opacity-60' : ''} ${feriado ? '' : 'text-gray-400'} uppercase\" style=\"color:${feriado ? (feriado.color || '#EF4444') : ''}\">${day.dow}</span><span class=\"text-sm font-black ${feriado ? '' : 'text-gray-800'}\" style=\"color:${feriado ? (feriado.color || '#EF4444') : ''}\">${day.num}</span></div>`;
        if (badge) {
            headerZone = headerZone.replace('mb-1','mb-0');
            headerZone += `<div class=\"w-fit max-w-full py-0.5 px-2 rounded-full mb-0 text-[7px] font-bold text-white flex items-center gap-1 shadow-sm\" style=\"background-color:${badge.color||'#4F46E5'}; margin-top:0; margin-bottom:0;\"><i class=\"ph-fill ph-${badge.icon||'calendar-blank'}\"></i> ${badge.title||'Tipo'}</div>`;
        }

        // --- ZONA CENTRO: eventos normales ---
        let centerZone = '';
        if (normalEvents.length > 0) {
            centerZone = `<div class=\"flex flex-col gap-0.5 flex-1 mt-1\">${normalEvents.map(ev => {
                const mode = ev.display_mode || 'block';
                const color = ev.color || '#4F46E5';
                const icon = ev.icon || 'calendar-blank';
                const name = ev.title || 'Tipo';
                return `<div class=\"event-card-preview\">${window.getCardHTML ? window.getCardHTML(mode, color, icon, name, {...ev, start: day.iso}) : ''}</div>`;
            }).join('')}</div>`;
        } else {
            centerZone = '<div class=\"flex-1\"></div>';
        }

        // --- ZONA ABAJO: línea decorativa ---
        let bottomZone = '<div class="w-3/4 h-1 rounded bg-gradient-to-r from-indigo-100 via-gray-50 to-indigo-100 border border-gray-100 shadow-sm opacity-80 mx-auto mt-1"></div>';

        // --- ENSAMBLAR CELDA ---
        // Overlay e icono para feriado
        let overlay = '';
        let label = '';
        if (feriado) {
            const color = feriado.color || '#EF4444';
            const icon = feriado.icon || 'calendar-blank';
            const name = feriado.title || 'FERIADO';
            overlay = `<div class=\"absolute inset-0 flex items-center justify-center opacity-20 pointer-events-none\"><i class=\"ph ph-${icon} text-4xl transform -rotate-12\" style=\"color:${color}\"></i></div>`;
            label = `<div class=\"absolute bottom-1 right-1 text-[6px] font-bold uppercase opacity-80 pointer-events-none\" style=\"color: ${color}\">${name}</div>`;
        }
        return `<div class='${contClass}' style='${style}'>
            <div class='w-full h-full flex flex-col ${feriado ? 'bg-transparent p-2' : 'bg-white p-2'} relative z-10'>
                ${headerZone}
                ${centerZone}
                ${bottomZone}
                ${label}
            </div>
            ${overlay}
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
            // Generar YYYY-MM-DD en local
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return {
                num: d.getDate(),
                dow: d.toLocaleDateString('es-ES', { weekday: 'short' }).toUpperCase().replace('.',''),
                iso: `${yyyy}-${mm}-${dd}`
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
                const dayEvents = events.filter(e => (e.event_date||'').slice(0,10) === d.iso);
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
