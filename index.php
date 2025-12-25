<?php
// index.php
require_once 'includes/functions.php';
$isGuest = !isset($_SESSION['user_id']);

// --- GENERAR FECHA EN ESPAÑOL (COMPATIBLE PHP 8+) ---
$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

$diaSemana = $dias[date('w')];
$diaNum = date('j');
$mesNom = $meses[date('n') - 1];
$anio = date('Y');

$dateText = "$diaSemana, $diaNum de $mesNom de $anio";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoy - EventApp</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Iconos Phosphor -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/event_preview.js"></script>
    <script src="js/render_event_card_clean.js"></script>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .animate-enter { animation: enter 0.5s ease-out forwards; opacity: 0; transform: translateY(20px); }
        @keyframes enter { to { opacity: 1; transform: translateY(0); } }
        
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-clamp: 2; }
    </style>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden text-slate-800">

    <!-- 1. Navbar Fijo -->
    <div class="shrink-0 z-30 shadow-sm bg-white">
        <?php include 'includes/navbar.php'; ?>
    </div>

    <!-- 2. Contenido Principal -->
    <main class="flex-1 container mx-auto px-4 py-8 flex flex-col min-h-0 overflow-y-auto custom-scroll">
        
        <!-- Header del Día -->
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 border-b border-gray-200 pb-4 animate-enter">
            <div>
                <p class="text-indigo-600 font-bold text-xs uppercase tracking-wider mb-1 flex items-center gap-1">
                    <i class="ph-fill ph-sun-horizon"></i> Tu Resumen Diario
                </p>
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 capitalize">
                    <?php echo $dateText; ?>
                </h1>
            </div>
            
            <div class="flex gap-3 mt-4 md:mt-0">
                <?php if(!$isGuest): ?>
                    <a href="calendar.php" class="flex items-center text-sm font-bold text-gray-500 hover:text-indigo-600 transition bg-white px-4 py-2.5 rounded-xl border border-gray-200 shadow-sm hover:shadow-md">
                        <i class="ph-bold ph-calendar-blank mr-2 text-lg"></i> Ver Mes
                    </a>
                    <a href="events.php" class="flex items-center text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg shadow-indigo-200">
                        <i class="ph-bold ph-plus-circle mr-2 text-lg"></i> Nuevo
                    </a>
                <?php else: ?>
                    <a href="login.php" class="flex items-center text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg">
                        <i class="ph-bold ph-sign-in mr-2 text-lg"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenedor de Tarjetas -->
        <div id="todayEventsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-enter" style="animation-delay: 0.1s;">
            <!-- Skeleton Loader -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-32 animate-pulse"></div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-32 animate-pulse"></div>
        </div>

        <!-- Estado Vacío -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-20 opacity-0 transition-opacity duration-500">
            <div class="bg-white p-6 rounded-full mb-4 shadow-sm border border-gray-100">
                <i class="ph-duotone ph-coffee text-6xl text-indigo-300"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-800">¡Todo despejado!</h2>
            <p class="text-gray-500 text-sm mt-1">No hay eventos programados para hoy.</p>
            <a href="calendar.php" class="mt-6 text-indigo-600 font-bold hover:underline text-sm flex items-center">
                Ver calendario completo <i class="ph-bold ph-arrow-right ml-1"></i>
            </a>
        </div>

    </main>

    <!-- 3. Footer Fijo -->
    <div class="shrink-0 bg-white border-t border-gray-200 z-30">
        <?php include 'includes/footer.php'; ?>
    </div>

    <script>
        const API_EVENTS = 'public/api/events.php';
        const API_TYPES = 'public/api/types.php';
        
        // Helper Color
        function hexToRgba(hex, alpha) {
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        async function loadTodayEvents() {
            // Fecha Local YYYY-MM-DD
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-CA'); // Formato ISO local seguro

            try {
                // Pedir eventos con un margen pequeño por seguridad
                const res = await fetch(`${API_EVENTS}?action=get_all&context=calendar&start_date=${dateStr}&end_date=${dateStr}`);
                const data = await res.json();
                const events = Array.isArray(data) ? data : (data.data||[]);
                
                // Filtrar estrictamente lo de HOY en el cliente
                const todayEvents = events.filter(ev => {
                    const start = ev.start.split('T')[0]; // YYYY-MM-DD
                    const end = ev.end ? ev.end.split('T')[0] : start;
                    
                    // Si el evento empieza hoy O si hoy está dentro del rango del evento
                    return (start <= dateStr && end >= dateStr);
                });

                renderEvents(todayEvents);
            } catch (error) {
                console.error("Error:", error);
                document.getElementById('todayEventsContainer').innerHTML = '<p class="text-red-500 text-center w-full">No se pudieron cargar los eventos.</p>';
            }
        }

        function renderEvents(events) {
            const container = document.getElementById('todayEventsContainer');
            const empty = document.getElementById('emptyState');

            if (events.length === 0) {
                container.classList.add('hidden');
                empty.classList.remove('hidden');
                setTimeout(() => empty.classList.remove('opacity-0'), 100);
                return;
            }

            container.innerHTML = events.map(ev => {
                    // Usar el renderer centralizado si está disponible
                    if (typeof window.renderEventCard === 'function') {
                        // Si es banner, renderizar como div limpio tipo franja
                        if ((ev.display_mode || '').toLowerCase() === 'banner') {
                            const color = ev.color || '#fb0e0e';
                            const icon = ev.icon || 'heart';
                            const name = ev.type_name || ev.title || '';
                            let img = '';
                            if (ev.image_url) {
                                img = `<div class=\"w-full h-24 bg-gray-100 flex items-center justify-center overflow-hidden\"><img src=\"${ev.image_url}\" class=\"object-cover w-full h-full\"></div>`;
                            }
                            // Card clickable
                            return `<div class=\"shadow-sm bg-white overflow-hidden group p-0 flex flex-col h-full cursor-pointer\" onclick=\"showEventDetail(${ev.id})\">\n                            <div class=\"h-4 w-full flex items-center justify-between px-1 shadow-sm z-10\" style=\"background-color:${color}\">\n                                <span class=\"text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1\"><i class=\"ph-bold ph-${icon}\"></i> ${name}</span>\n                            </div>\n                            ${img}\n                        </div>`;
                    }
                    return window.renderEventCard(ev);
                }
                // ...existing code...
            }).join('');
        }

        loadTodayEvents();
    </script>
</body>
</html>