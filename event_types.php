<?php
// event_types.php
require_once 'includes/functions.php';
requireAuth();
if (!hasRole(['admin', 'moderator'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tipos de Eventos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .fancy-scroll { overflow-y: auto; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
        .fancy-scroll::-webkit-scrollbar { width: 6px; }
        .fancy-scroll::-webkit-scrollbar-track { background: transparent; }
        .fancy-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; border: 2px solid transparent; background-clip: content-box; }
        .fancy-scroll::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

        /* UI Controls */
        .mode-radio:checked + div { border-color: #4F46E5; background-color: #EEF2FF; color: #4F46E5; box-shadow: 0 0 0 2px #4F46E5; }
        .mode-radio:checked + div .check-icon { opacity: 1; transform: scale(1); }
        .icon-radio:checked + label { background-color: #4F46E5; color: white; border-color: #4F46E5; transform: scale(1.05); }
        
        .cal-radio:checked + div { border-color: #4F46E5; background-color: #EEF2FF; color: #4F46E5; box-shadow: 0 0 0 2px #4F46E5; }
        .card-radio:checked + div { border-color: #4F46E5; background-color: #F9FAFB; box-shadow: 0 0 0 2px #4F46E5; transform: translateY(-2px); }
        
        .section-disabled { opacity: 0.5; pointer-events: none; filter: grayscale(0.8); transition: all 0.3s; }

        /* Nuevas animaciones */
        .preview-fade { animation: previewFade 0.3s ease-out; }
        @keyframes previewFade { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .icon-hover { transition: all 0.2s; }
        .icon-hover:hover { transform: scale(1.1); background-color: #F3F4F6 !important; }
    </style>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden">
    
    <div class="shrink-0 bg-white shadow-sm z-20 relative">
        <?php include 'includes/navbar.php'; ?>
    </div>

    <main class="flex-1 max-w-6xl mx-auto w-full py-8 px-4 min-h-0 flex flex-col">
        
        <div class="flex justify-between items-center mb-6 shrink-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="ph ph-paint-brush-broad mr-3 text-indigo-600 bg-indigo-50 p-2 rounded-lg"></i> 
                    Configuración de Tipos
                </h1>
            </div>
            <button onclick="openModal()" class="bg-gray-900 hover:bg-gray-800 text-white px-5 py-2.5 rounded-xl shadow-lg font-bold text-sm flex items-center transition transform hover:-translate-y-0.5">
                <i class="ph ph-plus-circle mr-2 text-lg"></i> Nuevo Tipo
            </button>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex-1 flex flex-col">
            <!-- Barra de búsqueda -->
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-3">
                    <div class="flex-1 relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input type="text" id="searchInput" placeholder="Buscar tipos de evento..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    </div>
                    <span id="resultCount" class="text-sm text-gray-500 font-medium">Cargando...</span>
                </div>
            </div>
            <div class="overflow-auto fancy-scroll flex-1">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50 sticky top-0 z-10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Identidad</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estilo Principal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Color</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="typesTable" class="bg-white divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Edición -->
    <div id="typeModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl h-[90vh] flex overflow-hidden">
            
            <!-- COLUMNA IZQUIERDA: FORMULARIO -->
            <div class="w-full md:w-8/12 p-8 overflow-y-auto fancy-scroll border-r border-gray-100 bg-white">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800" id="modalTitle">Nuevo Tipo</h2>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="ph ph-x text-xl"></i></button>
                </div>

                <form id="typeForm" class="space-y-8">
                    <input type="hidden" name="id" id="typeId">
                    <input type="hidden" name="display_mode" id="realDisplayMode" value="block">
                    
                    <!-- IDENTIDAD -->
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Nombre</label>
                            <input type="text" name="name" id="typeName" required placeholder="Ej: Reunión" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" oninput="updatePreview()">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Color Base</label>
                            <div class="flex items-center h-[42px] border border-gray-300 rounded-lg overflow-hidden px-1 hover:border-indigo-300 transition">
                                <input type="color" name="color" id="typeColor" value="#4F46E5" class="h-8 w-full border-0 cursor-pointer bg-transparent" oninput="updatePreview()">
                            </div>
                        </div>
                    </div>

                    <!-- 1. SECCIÓN CALENDARIO MENSUAL -->
                    <div class="bg-indigo-50/50 p-5 rounded-2xl border border-indigo-100">
                        <label class="text-[11px] font-bold text-indigo-500 mb-3 block uppercase tracking-wider">
                            1. Configuración de Día (Calendario Mensual)
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Defecto -->
                            <label class="cursor-pointer group relative" title="Muestra el evento como un punto estándar dentro del día, usando el estilo de tarjeta seleccionado.">
                                <input type="radio" name="cal_view" value="default" class="cal-radio hidden" onchange="updatePreview()" checked>
                                <div class="border border-white bg-white rounded-xl p-3 hover:border-indigo-300 transition flex items-center gap-3 shadow-sm h-full">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center"><div class="w-2 h-2 bg-gray-400 rounded-full"></div></div>
                                    <div><span class="block text-sm font-bold text-gray-800">Punto Estándar</span><span class="block text-[10px] text-gray-500 leading-tight">Usa tarjeta interna.</span></div>
                                    <i class="ph ph-check-circle-fill absolute top-2 right-2 text-indigo-600 text-lg opacity-0 transition-all mode-icon"></i>
                                </div>
                            </label>
                            <!-- Etiqueta -->
                            <label class="cursor-pointer group relative" title="Muestra una etiqueta compacta en la parte superior del día.">
                                <input type="radio" name="cal_view" value="badge" class="cal-radio hidden" onchange="updatePreview()">
                                <div class="border border-white bg-white rounded-xl p-3 hover:border-indigo-300 transition flex items-center gap-3 shadow-sm h-full">
                                    <div class="w-8 h-8 bg-white border border-gray-200 rounded flex flex-col p-1 justify-center gap-1"><div class="h-1.5 bg-indigo-500 rounded-sm"></div></div>
                                    <div><span class="block text-sm font-bold text-gray-800">Etiqueta (Badge)</span><span class="block text-[10px] text-gray-500 leading-tight">Barra compacta.</span></div>
                                </div>
                            </label>
                            <!-- Cinta -->
                            <label class="cursor-pointer group relative" title="Agrega una cinta de color en la parte superior del día.">
                                <input type="radio" name="cal_view" value="banner" class="cal-radio hidden" onchange="updatePreview()">
                                <div class="border border-white bg-white rounded-xl p-3 hover:border-indigo-300 transition flex items-center gap-3 shadow-sm h-full">
                                    <div class="w-8 h-8 bg-white border border-gray-200 rounded flex flex-col overflow-hidden"><div class="h-2.5 bg-indigo-500 w-full"></div></div>
                                    <div><span class="block text-sm font-bold text-gray-800">Cinta Superior</span><span class="block text-[10px] text-gray-500 leading-tight">Destaca encabezado.</span></div>
                                </div>
                            </label>
                            <!-- Fondo -->
                            <label class="cursor-pointer group relative" title="Pinta todo el fondo del día con el color seleccionado.">
                                <input type="radio" name="cal_view" value="background" class="cal-radio hidden" onchange="updatePreview()">
                                <div class="border border-white bg-white rounded-xl p-3 hover:border-indigo-300 transition flex items-center gap-3 shadow-sm h-full">
                                    <div class="w-8 h-8 bg-indigo-100 border border-indigo-200 rounded flex items-center justify-center text-[8px] font-bold text-indigo-600">24</div>
                                    <div><span class="block text-sm font-bold text-gray-800">Fondo Completo</span><span class="block text-[10px] text-gray-500 leading-tight">Pinta todo el día.</span></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. SECCIÓN ESTILO DE TARJETA -->
                    <div id="sectionCards" class="transition-all duration-300">
                        <label class="text-[11px] font-bold text-gray-400 mb-3 block uppercase tracking-wider ml-1">
                            2. Estilo de Tarjeta (Contenido del Evento)
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-3">
                            <label class="cursor-pointer group text-center" title="Estilo básico con borde izquierdo de color."><input type="radio" name="card_view" value="block" checked class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-white border-l-4 border-gray-400 shadow-sm mb-2"></div><span class="text-[10px] font-bold text-gray-600">Estándar</span></div></label>
                            <label class="cursor-pointer group text-center" title="Fondo sutil con borde de color."><input type="radio" name="card_view" value="subtle" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-indigo-50 border border-indigo-100 shadow-sm mb-2"></div><span class="text-[10px] font-bold text-gray-600">Suave</span></div></label>
                            <label class="cursor-pointer group text-center" title="Degradado de color a blanco."><input type="radio" name="card_view" value="gradient" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-gradient-to-br from-white to-indigo-200 shadow-sm mb-2 border border-indigo-100"></div><span class="text-[10px] font-bold text-gray-600">Aero</span></div></label>
                            <label class="cursor-pointer group text-center" title="Fondo sólido de color con texto blanco."><input type="radio" name="card_view" value="important" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-gray-800 shadow-sm mb-2"></div><span class="text-[10px] font-bold text-gray-600">Sólido</span></div></label>
                            <label class="cursor-pointer group text-center" title="Tarjeta grande con avatar e icono."><input type="radio" name="card_view" value="detailed" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-gray-50 flex items-center justify-center border border-gray-200 mb-2"><i class="ph-fill ph-list text-[8px] text-gray-400"></i></div><span class="text-[10px] font-bold text-gray-600">Detallada</span></div></label>
                            <label class="cursor-pointer group text-center" title="Tarjeta con imagen de fondo."><input type="radio" name="card_view" value="photo" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 bg-gray-50 flex items-center justify-center border border-gray-200 mb-2"><i class="ph-fill ph-image text-[8px] text-gray-400"></i></div><span class="text-[10px] font-bold text-gray-600">Foto</span></div></label>
                            <label class="cursor-pointer group text-center" title="Estilo minimalista con borde punteado."><input type="radio" name="card_view" value="transparent" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 border border-dashed border-gray-400 mb-2"></div><span class="text-[10px] font-bold text-gray-600">Minimal</span></div></label>
                            <label class="cursor-pointer group text-center" title="Versión compacta del estándar."><input type="radio" name="card_view" value="compact" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-3 bg-white border-l-2 border-gray-400 shadow-sm mb-2"></div><span class="text-[10px] font-bold text-gray-600">Compacto</span></div></label>
                            <label class="cursor-pointer group text-center" title="Borde punteado de color."><input type="radio" name="card_view" value="outline" class="card-radio hidden" onchange="updatePreview()"><div class="border border-gray-200 rounded-xl p-3 hover:border-indigo-300 transition h-full flex flex-col items-center justify-center bg-white"><div class="w-8 h-5 border-2 border-dashed border-indigo-400 mb-2"></div><span class="text-[10px] font-bold text-gray-600">Contorno</span></div></label>
                        </div>
                    </div>

                    <!-- SELECTOR ICONOS -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-gray-700 uppercase tracking-wide">Icono</label>
                            <input type="text" id="iconSearch" placeholder="Buscar..." class="text-xs border border-gray-300 rounded-full px-3 py-1 outline-none focus:border-indigo-500 w-28 transition">
                        </div>
                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-1">
                            <div class="grid grid-cols-10 gap-1 h-28 overflow-y-auto fancy-scroll p-1" id="iconGrid"></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white border border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-50 text-sm">Cancelar</button>
                        <button type="button" id="deleteBtn" onclick="deleteType(document.getElementById('typeId').value)" class="hidden px-5 py-2.5 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 text-sm">Eliminar</button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-md text-sm">Guardar</button>
                    </div>
                </form>
            </div>

            <!-- COLUMNA DERECHA: PREVIEW UNIFICADA (3x3 con Zoom Extremo) -->
            <div class="w-full md:w-5/12 bg-gray-50 flex flex-col items-center justify-center border-l border-gray-200 relative p-8">
                <div class="absolute top-4 right-4 text-[10px] text-gray-300 font-mono tracking-widest font-bold">PREVIEW</div>
                
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6">Resultado en Calendario</h3>
                
                <!-- PREVIEW GRID 3x3 ASIMÉTRICO (ZOOM EN MEDIO) -->
                <!-- COLUMNAS: [Muy pequeña] [MUY GRANDE] [Muy pequeña] -->
                <div class="w-full max-w-[340px] aspect-square bg-gray-200 border border-gray-300 rounded-xl shadow-inner overflow-hidden grid grid-cols-[0.4fr_3fr_0.4fr] grid-rows-[0.4fr_3fr_0.4fr] gap-px text-[9px] text-gray-400">
                    
                    <!-- Fila 1 (Pasada) -->
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">16</span></div>
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">17</span></div>
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">18</span></div>
                    
                    <!-- Fila 2 (Presente) -->
                    <div class="bg-gray-50 p-1 flex flex-col justify-between opacity-60">
                        <div class="text-right w-full font-bold">23</div>
                        <div class="w-full h-1 bg-gray-200 rounded"></div>
                    </div>
                    
                    <!-- CELDA CENTRAL (PROTAGONISTA - MIÉRCOLES) -->
                    <div id="previewCell" class="bg-white relative flex flex-col overflow-hidden z-10 shadow-2xl ring-4 ring-indigo-50/50 transform transition-all duration-300">
                        <!-- JS DIBUJA AQUÍ -->
                    </div>
                    
                    <div class="bg-gray-50 p-1 flex flex-col justify-between opacity-60">
                        <div class="text-right w-full font-bold">25</div>
                        <div class="w-full h-1 bg-gray-200 rounded mb-1"></div>
                    </div>
                    
                    <!-- Fila 3 (Futura) -->
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">30</span></div>
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">1</span></div>
                    <div class="bg-gray-50 p-1 flex flex-col items-end opacity-40"><span class="font-bold">2</span></div>
                </div>

                <!-- Preview Agenda (Lista) -->
                <div class="w-full flex flex-col items-center mt-8 pt-6 border-t border-gray-200/50">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 text-center">En la Agenda (Lista)</h3>
                    <div id="previewCard" class="w-64 transition-all duration-300">
                        <!-- JS DIBUJA AQUÍ -->
                    </div>
                </div>

                <p class="text-[11px] text-gray-400 mt-6 text-center px-4 max-w-xs leading-relaxed opacity-80" id="previewText">
                    Configuración seleccionada.
                </p>
            </div>
        </div>
    </div>

    <div class="shrink-0 mt-auto bg-white border-t"><?php include 'includes/footer.php'; ?></div>

    <script>
        const API = 'public/api/types.php';
        const allIcons = ['calendar-blank','star','warning','check-circle','info','users','confetti','briefcase','airplane','first-aid','graduation-cap','house','lightbulb','megaphone','push-pin','rocket','trophy','video-camera','whatsapp-logo','heart','fire','bell','clock','flag','globe','coffee','laptop','gift','sun','moon','cloud-rain','snowflake','music-note','camera','car','bus','train','bicycle','walking','map-pin','phone','envelope','chat','lock','key','gear','sliders','chart-bar','chart-pie','presentation','book','shield'];

        function initIcons(filter='') {
            const grid = document.getElementById('iconGrid');
            grid.innerHTML = allIcons.filter(i=>i.includes(filter.toLowerCase())).map(icon => `
                <div class="relative group">
                    <input type="radio" name="icon" id="icon_${icon}" value="${icon}" class="icon-radio hidden peer" onchange="updatePreview()">
                    <label for="icon_${icon}" class="icon-hover flex items-center justify-center h-8 w-8 rounded-md border border-transparent cursor-pointer hover:bg-white hover:border-gray-200 transition text-gray-400 group-hover:text-indigo-500 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:scale-110 peer-checked:shadow-md" title="${icon.replace('-',' ')}">
                        <i class="ph ph-${icon} text-lg"></i>
                    </label>
                </div>`).join('');
            const selected = document.querySelector(`input[name="icon"][checked]`);
            if(selected && !filter) document.getElementById(selected.id).checked = true;
        }

        document.getElementById('searchInput').addEventListener('input', filterTypes);

        // --- LÓGICA PRINCIPAL UNIFICADA (updatePreview) ---
        function updatePreview() {
            const calMode = document.querySelector('input[name="cal_view"]:checked').value;
            const cardMode = document.querySelector('input[name="card_view"]:checked').value;
            const sectionCards = document.getElementById('sectionCards');
            const realInput = document.getElementById('realDisplayMode');
            const helpText = document.getElementById('previewText');
            const color = document.getElementById('typeColor').value;
            const icon = document.querySelector('input[name="icon"]:checked')?.value || 'circle';

            // Actualizar iconos visualmente
            document.querySelectorAll('.icon-radio + label').forEach(l => l.className="flex items-center justify-center h-8 w-8 rounded-md border border-transparent cursor-pointer hover:bg-white hover:border-gray-200 text-gray-400 transition");
            const activeIcon = document.querySelector('.icon-radio:checked + label');
            if(activeIcon) activeIcon.className="flex items-center justify-center h-8 w-8 rounded-md bg-indigo-600 text-white shadow-md transform scale-110 cursor-pointer transition";

            if (calMode === 'default') {
                sectionCards.classList.remove('section-disabled');
                realInput.value = cardMode; 
                helpText.innerText = "Modo Estándar: El evento se muestra dentro del día usando el estilo de tarjeta seleccionado.";
            } else {
                sectionCards.classList.add('section-disabled');
                realInput.value = calMode; 
                helpText.innerText = "Modo Especial: Destaca el día o encabezado. El estilo de tarjeta se ignora en la vista mensual.";
            }

            renderUnifiedPreview(calMode, cardMode, color, icon);
            renderCardPreview(calMode, cardMode, color, icon);
        }

        // --- GENERADOR DE HTML DE TARJETA (REUTILIZABLE)
        // Use shared implementation if available; otherwise define locally
        if (typeof window.getCardHTML !== 'function') {
            window.getCardHTML = function(cardMode, color, icon, name) {
                let innerStyle = '', innerText = 'text-gray-700', innerIconColor = color;
                let contentHTML = `<i class="ph ph-${icon}" style="color:${innerIconColor}"></i> <span style="${innerText}">${name}</span>`;

                if (cardMode === 'block') { innerStyle=`background:#fff; border-left:3px solid ${color}; box-shadow:0 1px 2px rgba(0,0,0,0.05);`; }
                else if (cardMode === 'subtle') { innerStyle=`background:${hexToRgba(color,0.15)}; color:${color}; border-left:3px solid ${color}; font-weight:600;`; innerText = `color:${color}`; contentHTML = `<i class="ph ph-${icon}" style="color:${color}"></i> <span style="color:${color}">${name}</span>`; }
                else if (cardMode === 'gradient') { innerStyle=`background:linear-gradient(135deg,${color},#ffffff 180%); color:white; border:1px solid ${color};`; innerText = `color:white; text-shadow:0 1px 2px rgba(0,0,0,0.3);`; contentHTML = `<i class="ph ph-${icon}" style="color:white"></i> <span style="color:white; text-shadow:0 1px 2px rgba(0,0,0,0.3);">${name}</span>`; }
                else if (cardMode === 'important') { innerStyle=`background:${color}; color:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.15); border: 1px solid rgba(0,0,0,0.1); border-left: 3px solid rgba(0,0,0,0.3);`; contentHTML = `<i class="ph-fill ph-${icon}" style="color:white"></i> <span style="color:white">${name}</span>`; }
                else if (cardMode === 'transparent') { innerStyle=`background:transparent; border:1px dashed ${color}; color:${color}; font-weight:500;`; innerText = `color:${color}`; contentHTML = `<i class="ph ph-${icon}" style="color:${color}"></i> <span style="color:${color}">${name}</span>`; }
                else if (cardMode === 'photo') { innerStyle = `background: white; border-left: 3px solid ${color}; padding: 0; display: flex; align-items: center; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1);`; contentHTML = `<div style="width: 20px; height: 20px; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="ph-fill ph-image text-[10px] text-gray-400"></i></div><span style="padding: 0 4px; font-size: 7px; color: #374151;">${name}</span>`; }

                else if (cardMode === 'detailed') {
                    innerStyle = `background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); border: 1px solid #f1f5f9; border-left: 4px solid ${color}; padding: 4px; display: flex; align-items: center; gap: 8px; margin: 2px 0; min-height: 40px;`;
                    const bgIcon = hexToRgba(color, 0.15);
                    contentHTML = `
                        <div style="width: 32px; height: 32px; border-radius: 50%; background-color: ${bgIcon}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: ${color}; box-shadow: inset 0 0 0 1px ${color}20;">
                            <i class="ph-fill ph-${icon} text-lg"></i>
                        </div>
                        <div style="display: flex; flex-direction: column; overflow: hidden; justify-content: center;">
                            <span style="font-weight: 800; font-size: 0.8rem; color: #1e293b; line-height: 1.1;">${name}</span>
                            <span style="font-size: 0.65rem; color: #64748b; margin-top: 1px;">Detalles...</span>
                        </div>
                    `;
                }

                return `
                    <div class="w-full ${cardMode!=='photo'&&cardMode!=='detailed'?'p-1.5':''} rounded text-[7px] truncate flex items-center gap-1 transition-transform transform hover:scale-[1.02] cursor-pointer shadow-sm mb-1" style="${innerStyle}">
                        ${contentHTML}
                    </div>`;
            };
        }

        // --- PREVIEW 3x3 REALISTA (SOLO CELDA CENTRAL) ---
        function renderUnifiedPreview(calMode, cardMode, color, icon) {
            const container = document.getElementById('previewCell');
            const name = document.getElementById('typeName').value || 'Tipo';
            let html = '';

            // CASO 1: PUNTO ESTÁNDAR -> TARJETA DENTRO DE CELDA
            if (calMode === 'default') {
                const cardHTML = getCardHTML(cardMode, color, icon, name);
                html = `
                    <div class="w-full h-full p-2 flex flex-col justify-between">
                        <div class="flex justify-between items-start">
                             <span class="text-[8px] font-bold text-gray-400 uppercase">MIÉ</span>
                             <span class="text-sm font-black text-gray-800">24</span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            ${cardHTML} <!-- TARJETA CLONADA -->
                            <div class="w-3/4 h-1 bg-gray-100 rounded opacity-50"></div>
                        </div>
                    </div>`;
            }

            // CASO 2: ETIQUETA (BADGE)
            else if (calMode === 'badge') {
                html = `
                    <div class="w-full h-full p-2 flex flex-col bg-white">
                        <div class="flex justify-between items-start mb-1">
                             <span class="text-[8px] font-bold text-gray-400 uppercase">MIÉ</span>
                             <span class="text-sm font-black text-gray-800">24</span>
                        </div>
                        <div class="w-fit max-w-full py-0.5 px-2 rounded-full mb-1 text-[7px] font-bold text-white flex items-center gap-1 shadow-sm" style="background-color:${color}">
                            <i class="ph-fill ph-${icon}"></i> ${name}
                        </div>
                        <div class="w-full h-2 rounded bg-gray-50 border border-gray-100 mt-auto"></div>
                    </div>`;
            }

            // CASO 3: FONDO COMPLETO
            else if (calMode === 'background') {
                const bgRgba = hexToRgba(color, 0.15);
                html = `
                    <div class="w-full h-full p-2 flex flex-col justify-between relative overflow-hidden" style="background-color: ${bgRgba};">
                        <div class="flex justify-between items-start z-10 relative">
                            <span class="text-[8px] font-black opacity-60" style="color:${color}">MIÉ</span>
                            <span class="text-sm font-black" style="color:${color}">24</span>
                        </div>
                        <div class="absolute inset-0 flex items-center justify-center opacity-20"><i class="ph ph-${icon} text-4xl transform -rotate-12" style="color:${color}"></i></div>
                        <div class="absolute bottom-1 right-1 text-[6px] font-bold uppercase opacity-80" style="color: ${color}">${name}</div>
                    </div>`;
            } 
            
            // CASO 4: CINTA (BANNER)
            else if (calMode === 'banner') {
                html = `
                    <div class="w-full h-full flex flex-col bg-white">
                        <div class="h-4 w-full flex items-center justify-between px-1 shadow-sm z-10" style="background-color:${color}">
                            <span class="text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1"><i class="ph-bold ph-${icon}"></i> ${name}</span>
                        </div>
                        <div class="p-2 relative flex-1">
                            <div class="flex justify-between items-start opacity-30 mb-1">
                                <span class="text-[8px] font-black">MIÉ</span>
                                <span class="text-sm font-black">24</span>
                            </div>
                            <div class="bg-gray-100 p-0.5 rounded w-3/4 mb-1 h-1"></div>
                        </div>
                    </div>`;
            }
            
            container.innerHTML = html;
            container.classList.add('preview-fade');
            setTimeout(() => container.classList.remove('preview-fade'), 300);
        }

        // --- PREVIEW AGENDA (TARJETA) ---
        function renderCardPreview(calMode, cardMode, color, icon) {
            const container = document.getElementById('previewCard');
            const name = document.getElementById('typeName').value || 'Tipo';
            
            if (calMode === 'banner') { container.innerHTML = `<div class="w-full flex justify-center items-center h-28"><div class="w-full p-2 rounded shadow-md text-white text-xs font-bold uppercase tracking-wider flex items-center justify-center gap-2 transform scale-110" style="background-color: ${color}"><i class="ph-bold ph-${icon}"></i> ${name}</div></div>`; return; }
            if (calMode === 'badge') { container.innerHTML = `<div class="w-full flex justify-center h-28 items-center"><div class="px-3 py-1.5 rounded-full text-white text-xs font-bold shadow-md flex items-center gap-2 transform scale-125" style="background-color: ${color}"><i class="ph-fill ph-${icon}"></i> ${name}</div></div>`; return; }

            const cardHTML = getCardHTML(cardMode, color, icon, name);
            // Escalar para la vista previa de Agenda
            const largeHTML = cardHTML.replace('text-[7px]', 'text-xs').replace('p-1.5', 'p-3').replace('mb-1', 'mb-0')
                                      .replace('width: 20px', 'width: 40px').replace('height: 20px', 'height: 40px') // photo
                                      .replace('width: 32px', 'width: 48px').replace('height: 32px', 'height: 48px') // detailed
                                      .replace('text-lg', 'text-2xl') // detailed icon
                                      .replace('text-sm', 'text-xl') 
                                      .replace('font-size: 0.8rem', 'font-size: 1.1rem') 
                                      .replace('font-size: 0.65rem', 'font-size: 0.85rem');

            container.innerHTML = `<div class="p-3 flex flex-col gap-2 scale-105"><div class="flex justify-between items-start"><div class="w-full">${largeHTML}</div></div></div>`;
            container.classList.add('preview-fade');
            setTimeout(() => container.classList.remove('preview-fade'), 300);
        }

        function hexToRgba(h,a){ let r=parseInt(h.slice(1,3),16), g=parseInt(h.slice(3,5),16), b=parseInt(h.slice(5,7),16); return `rgba(${r},${g},${b},${a})`; }

        // --- CRUD ---
        let allTypes = [];
        async function loadTypes() {
            const res = await fetch(`${API}?action=list`); // USAR RUTA MODULAR
            allTypes = await res.json();
            filterTypes();
        }

        function filterTypes() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allTypes.filter(t => t.name.toLowerCase().includes(query) || t.display_mode.toLowerCase().includes(query));
            document.getElementById('resultCount').innerText = `${filtered.length} tipo${filtered.length !== 1 ? 's' : ''}`;
            document.getElementById('typesTable').innerHTML = filtered.map(t => {
                let lbl = 'Estándar';
                if(['background','banner'].includes(t.display_mode)) lbl = 'Día Especial';
                else if(t.display_mode === 'badge') lbl = 'Etiqueta';
                else if(t.display_mode==='photo') lbl = 'Tarjeta Foto';
                else if(t.display_mode==='detailed') lbl = 'Tarjeta Detallada';
                else if(t.display_mode!=='block') lbl = 'Tarjeta: ' + t.display_mode.charAt(0).toUpperCase() + t.display_mode.slice(1);

                return `
                <tr class="hover:bg-gray-50 border-b border-gray-50 transition">
                    <td class="px-6 py-4 flex items-center">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center mr-3 text-white shadow-sm transition" style="background-color:${t.color}"><i class="ph ph-${t.icon} text-lg"></i></div>
                        <span class="font-bold text-sm text-gray-800">${t.name}</span>
                    </td>
                    <td class="px-6 py-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs border border-gray-200 font-medium">${lbl} (${t.display_mode})</span></td>
                    <td class="px-6 py-4"><div class="w-4 h-4 rounded-full border border-gray-200" style="background-color:${t.color}"></div></td>
                    <td class="px-6 py-4 text-right">
                        <button onclick='editType(${JSON.stringify(t)})' class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition mr-1" title="Editar"><i class="ph ph-pencil-simple text-lg"></i></button>
                        <button onclick="deleteType(${t.id})" class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition" title="Eliminar"><i class="ph ph-trash text-lg"></i></button>
                    </td>
                </tr>`;
            }).join('');
        }

        function editType(t) {
            document.getElementById('modalTitle').innerText = 'Editar Tipo';
            document.getElementById('typeId').value = t.id;
            document.getElementById('typeName').value = t.name;
            document.getElementById('typeColor').value = t.color;
            initIcons();
            if (['background','banner','badge'].includes(t.display_mode)) {
                document.querySelector(`input[name="cal_view"][value="${t.display_mode}"]`).checked = true;
                document.querySelector(`input[name="card_view"][value="block"]`).checked = true;
            } else {
                document.querySelector(`input[name="cal_view"][value="default"]`).checked = true;
                
                // Mapeo seguro si es detailed
                const cr = document.querySelector(`input[name="card_view"][value="${t.display_mode}"]`);
                if(cr) cr.checked = true;
                else document.querySelector(`input[name="card_view"][value="block"]`).checked = true;
            }
            setTimeout(() => { const i=document.querySelector(`input[name="icon"][value="${t.icon}"]`); if(i)i.checked=true; updatePreview(); }, 50);
            // Mostrar botón eliminar si es edición
            const deleteBtn = document.getElementById('deleteBtn');
            if (t.id) {
                deleteBtn.classList.remove('hidden');
            } else {
                deleteBtn.classList.add('hidden');
            }
            document.getElementById('typeModal').classList.remove('hidden');
        }

        async function deleteType(id) { 
            const result = await Swal.fire({
                title: '¿Eliminar tipo?',
                text: '¿Estás seguro de eliminar este tipo de evento? Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            if (!result.isConfirmed) return;
            const res=await fetch(`${API}?action=delete`,{method:'POST',body:JSON.stringify({id})}); 
            const r = await res.json(); 
            if(r.success){
                await Swal.fire({
                    title: '¡Eliminado!',
                    text: 'Tipo de evento eliminado correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#4F46E5'
                });
                closeModal(); loadTypes();
            } else { 
                await Swal.fire({
                    title: 'Error',
                    text: r.message || 'Error al eliminar.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
        }
        document.getElementById('typeForm').addEventListener('submit', async e=>{
            e.preventDefault();
            const name = document.getElementById('typeName').value.trim();
            if (!name) {
                await Swal.fire({
                    title: 'Campo requerido',
                    text: 'El nombre del tipo no puede estar vacío.',
                    icon: 'warning',
                    confirmButtonColor: '#4F46E5'
                });
                return;
            }
            const res=await fetch(`${API}?action=save`,{method:'POST',body:JSON.stringify(Object.fromEntries(new FormData(e.target)))});
            const result = await res.json();
            if(result.success){
                await Swal.fire({
                    title: '¡Guardado!',
                    text: 'Tipo de evento guardado correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#4F46E5'
                });
                closeModal();loadTypes();
            } else { 
                await Swal.fire({
                    title: 'Error',
                    text: result.message || 'Error al guardar.',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
        });
        function openModal(){ document.getElementById('typeForm').reset(); document.getElementById('typeId').value=''; document.getElementById('modalTitle').innerText='Nuevo'; document.getElementById('typeColor').value='#4F46E5'; initIcons(); document.querySelector('input[value="default"]').checked=true; document.querySelector('input[value="block"]').checked=true; document.getElementById('deleteBtn').classList.add('hidden'); updatePreview(); document.getElementById('typeModal').classList.remove('hidden'); }
        function closeModal(){ document.getElementById('typeModal').classList.add('hidden'); }
        
        initIcons();
        loadTypes();
    </script>
</body>
</html>