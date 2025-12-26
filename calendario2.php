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
    $sql = "SELECT eve.title, eve.color, eve.event_date, tev.slug as type_slug FROM events eve, event_types tev where eve.event_type_id = tev.id AND eve.event_date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $eventos[] = $row;
    }
    ?>
    <script>
    const eventos = <?php echo json_encode($eventos); ?>;
    renderCalendarPreview(eventos);

    function renderCalendarPreview(events) {
        const startDate = new Date('2025-12-15');
        const days = Array.from({length: 28}, (_,i) => {
            const d = new Date(startDate);
            d.setDate(startDate.getDate() + i);
            return {
                num: d.getDate(),
                dow: d.toLocaleDateString('es-ES', { weekday: 'short' }).toUpperCase().replace('.',''),
                iso: d.toISOString().slice(0,10)
            };
        });
        const container = document.getElementById('calendarPreview');
        const weekDays = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
        container.innerHTML = `
        <div class='w-full max-w-5xl bg-white shadow-lg overflow-hidden border border-gray-200'>
            <div class='grid grid-cols-7 bg-gray-50 border-b border-gray-200'>
                ${weekDays.map(day => `<div class='text-center py-2 font-bold text-gray-500 text-[15px] uppercase'>${day}</div>`).join('')}
            </div>
            <div class='aspect-[7/4] grid grid-cols-7 grid-rows-4 gap-px text-[16px] text-gray-700 mx-auto p-0'>
            ${days.map((d,i)=>{
                // Obtener todos los eventos de este día
                const dayEvents = events.filter(e => e.event_date === d.iso);
                // Detectar si es feriado
                const feriado = dayEvents.find(e => e.type_slug === 'holiday');
                // Determinar clase de contenedor: solo aplicar fondo/aro si hay feriado
                let contClass = 'bg-white relative flex flex-col overflow-hidden z-10 transition-all duration-300 min-h-[110px] min-w-[0] aspect-square border border-gray-100'; // sin rounded
                if (feriado) {
                    contClass += ' ring-2 ring-red-200';
                } else {
                    contClass += ' hover:ring-2 hover:ring-indigo-100';
                }

                // BADGES (display_mode = 'badge')
                const badges = dayEvents.filter(e => e.display_mode === 'badge');
                const badgesHtml = badges.map(ev => window.getCalendarCellHTML ? window.getCalendarCellHTML('badge', 'badge', ev.color||'#4F46E5', ev.icon||'calendar-blank', ev.title, {...ev, start: d.iso}) : '').join('');

                // HEADER: número y nombre de día
                let headerHtml = `<div class='flex justify-between items-center px-2 pt-1'>
                        <span class='text-[10px] font-bold text-gray-400'>${d.dow}</span>
                        <span class='text-xl font-black text-gray-800'>${d.num}</span>
                    </div>`;

                // EVENTOS (tarjetas, excluyendo holiday y badge)
                const eventCards = dayEvents.filter(e => e.display_mode !== 'badge' && e.type_slug !== 'holiday');
                const cardsHtml = eventCards.map(ev => {
                    // Usa el display_mode para el tipo de tarjeta
                    const mode = ev.display_mode || 'block';
                    return window.getCalendarCellHTML ? window.getCalendarCellHTML('default', mode, ev.color||'#4F46E5', ev.icon||'calendar-blank', ev.title, {...ev, start: d.iso}) : '';
                }).join('');

                // FERIADO TEXTO AL FINAL (eliminado para dejar solo el fondo tipo preview)
                let feriadoText = '';

                // FONDO DE FERIADO COMO EN event_types.php (preview)
                let holidayBg = '';
                if (feriado) {
                    // Solo el fondo y el icono, sin header ni número de día
                    if (window.getCalendarCellHTML) {
                        // Generar el HTML y quitar el header y número de día
                        let rawBg = window.getCalendarCellHTML('background', 'block', feriado.color||'#EF4444', feriado.icon||'calendar-blank', ` ${feriado.title||'FERIADO'}`, {...feriado, start: d.iso});
                        // Quitar el header y número de día (span con text-[8px] y text-sm)
                        rawBg = rawBg.replace(/<span[^>]*text-\[8px\][^>]*>.*?<\/span>/g, '').replace(/<span[^>]*text-sm[^>]*>.*?<\/span>/g, '');
                        holidayBg = rawBg;
                    }
                }

                return `<div class='${contClass} relative'>
                    ${holidayBg ? `<div class='absolute inset-0 pointer-events-none z-0'>${holidayBg}</div>` : ''}
                    <div class='relative z-10 flex flex-col h-full'>
                        ${badgesHtml}
                        ${headerHtml}
                        <div class='flex-1 flex flex-col items-center justify-center gap-0.5 w-full px-1 pb-1'>${cardsHtml}</div>
                    </div>
                </div>`;
            }).join('')}
            </div>
        </div>`;
    }
    </script>
</body>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
