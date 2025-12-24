<?php
// index.php sss
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
    <script src="js/render_event_card.js"></script>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .animate-enter { animation: enter 0.5s ease-out forwards; opacity: 0; transform: translateY(20px); }
        @keyframes enter { to { opacity: 1; transform: translateY(0); } }
        
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
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
                    return window.renderEventCard(ev);
                }
                
                // Fallback manual si no carga el script
                const mode = ev.display_mode || 'block';
                const color = ev.color || '#6B7280';
                const icon = ev.icon || 'circle';
                const title = ev.title.replace('⏳ ','').replace('🎂 ','');
                const isBirthday = ev.type === 'birthday';
                
                let style = '', badgeStyle = '', titleColor = '#1F2937';
                
                // 1. CUMPLEAÑOS / DETALLADO (Diseño Grande)
                if (isBirthday || mode === 'detailed' || mode === 'photo') {
                     let avatar = '';
                     if (ev.image_url) {
                         avatar = `<img src="${ev.image_url}" class="w-14 h-14 ${isBirthday?'rounded-full':'rounded-lg'} object-cover border-4 border-white shadow-md">`;
                     } else {
                         avatar = `<div class="w-14 h-14 ${isBirthday?'rounded-full':'rounded-lg'} flex items-center justify-center text-white shadow-md text-2xl" style="background-color:${color}"><i class="ph-fill ph-${isBirthday?'cake':icon}"></i></div>`;
                     }

                     style = `background: white; border: 1px solid #F1F5F9; border-left: 6px solid ${color}; display: flex; align-items: center; padding: 1.25rem;`;
                     
                     let extra = ev.type_name || 'Evento';
                     if(ev.age) extra = `<span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full text-xs font-bold border border-indigo-100">🎂 ${ev.age} Años</span>`;
                     else extra = `<span class="text-xs text-gray-400 font-medium uppercase tracking-wider">${extra}</span>`;

                     return `
                         <div class="rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group" style="${style}">
                             <div class="mr-4 flex-shrink-0">${avatar}</div>
                             <div class="flex-1 min-w-0">
                                 <h3 class="text-lg font-bold text-gray-800 leading-tight truncate mb-1" title="${title}">${title}</h3>
                                 ${extra}
                             </div>
                         </div>`;
                }

                // 2. OTROS ESTILOS (Adaptados a tarjeta grande de Dashboard)
                if (mode === 'block') { style = `background: white; border-left: 6px solid ${color};`; badgeStyle = `background: #F3F4F6; color: #4B5563;`; }
                else if (mode === 'subtle') { style = `background: ${hexToRgba(color, 0.05)}; border: 1px solid ${hexToRgba(color, 0.2)};`; titleColor = color; badgeStyle = `background: white; color: ${color}; border: 1px solid ${hexToRgba(color,0.2)}`; }
                else if (mode === 'gradient') { style = `background: linear-gradient(135deg, ${color}, #ffffff 250%); border: 1px solid ${color};`; titleColor = 'white'; badgeStyle = `background: rgba(255,255,255,0.25); color: white;`; }
                else if (mode === 'important' || mode === 'banner') { style = `background: ${color}; color: white;`; titleColor = 'white'; badgeStyle = `background: rgba(0,0,0,0.2); color: white;`; }
                else if (mode === 'background') { style = `background: ${hexToRgba(color, 0.15)}; border: 2px solid ${color};`; titleColor = color; badgeStyle=`background:white; color:${color}`; }
                else { style = `background: white; border-top: 4px solid ${color};`; badgeStyle = `background: #F3F4F6; color: #4B5563;`; } // Default fallback

                // Hora
                let timeHTML = '';
                if (!ev.allDay && ev.start.includes('T')) {
                    const time = ev.start.split('T')[1].substring(0,5);
                    timeHTML = `<div class="flex items-center text-xs font-bold mb-3 opacity-80" style="color:${titleColor}"><i class="ph-bold ph-clock mr-1.5"></i> ${time}</div>`;
                } else {
                    timeHTML = `<div class="flex items-center text-xs font-bold mb-3 opacity-80" style="color:${titleColor}"><i class="ph-bold ph-calendar-check mr-1.5"></i> Todo el día</div>`;
                }
                
                // Imagen portada opcional
                let heroImg = '';
                if (ev.image_url) heroImg = `<div class="h-32 w-full rounded-lg mb-3 overflow-hidden bg-gray-100"><img src="${ev.image_url}" class="w-full h-full object-cover"></div>`;

                return `
                <div class="rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group p-5 flex flex-col h-full" style="${style}">
                    ${(mode === 'background') ? `<i class="ph ph-${icon} absolute -right-6 -bottom-6 text-9xl opacity-10 transform -rotate-12 pointer-events-none" style="color:${color}"></i>` : ''}
                    
                    <div class="flex justify-between items-start mb-2">
                         <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide" style="${badgeStyle}">
                            <i class="ph-bold ph-${icon} mr-1.5"></i> ${ev.type_name || 'Evento'}
                        </span>
                    </div>

                    ${heroImg}

                    <h3 class="text-xl font-bold leading-tight mb-2 line-clamp-2" style="color:${titleColor}" title="${title}">${title}</h3>
                    
                    ${timeHTML}
                    
                    ${ev.description ? `<p class="text-sm opacity-70 line-clamp-2 mt-auto" style="color:${titleColor}">${ev.description}</p>` : ''}
                </div>`;
            }).join('');
        }

        loadTodayEvents();
    </script>
</body>
</html>