<!-- includes/events_modal_partial.php -->
<div id="formModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col transform scale-100 transition-transform">
        
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center shrink-0 bg-white rounded-t-2xl z-10">
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2" id="modalTitle">
                <i class="ph-duotone ph-calendar-plus text-indigo-600"></i> <span >Evento</span>
            </h2>
            <button type="button" onclick="closeFormModal()" class="text-gray-400 hover:text-gray-600 transition p-1 hover:bg-gray-100 rounded-full">
                <i class="ph-bold ph-x text-xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto custom-scroll p-6">
            <form id="eventForm" class="space-y-5">
                <input type="hidden" name="id" id="eId">
                
                <!-- Selector de Propietario (Solo Admin via JS) -->
                <div id="adminCreatorField" class="hidden bg-indigo-50 p-3 rounded-xl border border-indigo-100 relative group">
                    <label class="block text-xs font-bold text-indigo-700 mb-1 uppercase tracking-wide">Propietario (Admin)</label>
                    <div class="relative">
                        <i class="ph ph-magnifying-glass absolute left-3 top-2.5 text-indigo-400"></i>
                        <input type="text" id="userSearchInput" placeholder="Buscar usuario..." class="w-full pl-9 pr-8 py-2 border-indigo-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <input type="hidden" name="created_by" id="eCreator">
                        <button type="button" id="clearUserBtn" class="hidden absolute right-2 top-2 text-gray-400 hover:text-red-500"><i class="ph-bold ph-x-circle text-lg"></i></button>
                    </div>
                    <ul id="userDropdown" class="hidden absolute left-0 right-0 z-50 bg-white border border-indigo-100 mt-1 rounded-lg shadow-xl max-h-40 overflow-y-auto custom-scroll"></ul>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Título</label>
                    <input type="text" name="title" id="eTitle" required class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition shadow-sm font-medium">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- INICIO -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Inicio</label>
                        <div class="flex flex-col gap-2">
                            <input type="date" name="date" id="eDate" required class="w-full border border-gray-300 p-2 rounded-lg text-sm">
                            <input type="time" name="start_time" id="eStartTime" class="w-full border border-gray-300 p-2 rounded-lg text-sm">
                        </div>
                    </div>
                    <!-- FIN -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Fin (Opcional)</label>
                        <div class="flex flex-col gap-2">
                            <input type="date" name="end_date" id="eEndDate" class="w-full border border-gray-300 p-2 rounded-lg text-sm">
                            <input type="time" name="end_time" id="eEndTime" class="w-full border border-gray-300 p-2 rounded-lg text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Tipo de Evento</label>
                    <select name="event_type_id" id="eTypeSelect" class="w-full border border-gray-300 p-2.5 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer shadow-sm"></select>
                </div>

                <!-- Imagen Tabs -->
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-xs font-bold text-gray-700 uppercase">Imagen de Portada</label>
                        <div class="flex space-x-2 text-xs">
                            <button type="button" id="tabUp" class="font-bold text-indigo-600 border-b-2 border-indigo-600 pb-0.5 transition" onclick="tgImg('up')">Subir</button>
                            <button type="button" id="tabUr" class="text-gray-500 hover:text-indigo-600 pb-0.5 transition" onclick="tgImg('ur')">URL</button>
                        </div>
                    </div>
                    
                    <div id="bUp" class="mt-2">
                        <input type="file" name="image" id="eFile" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200 transition cursor-pointer">
                    </div>
                    <div id="bUr" class="hidden mt-2">
                        <input type="url" name="image_url_input" id="eUrl" placeholder="https://ejemplo.com/imagen.jpg" class="w-full border border-gray-300 p-2 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div id="iPr" class="mt-3 hidden relative bg-white p-2 rounded-lg border border-dashed border-gray-300 text-center">
                        <img src="" class="h-32 w-full object-cover rounded shadow-sm mx-auto">
                        <button type="button" onclick="clearImage()" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow-md hover:bg-red-600 transition" title="Eliminar imagen"><i class="ph-bold ph-trash"></i></button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Visibilidad</label>
                        <select name="visibility" id="eVis" class="w-full border border-gray-300 p-2.5 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="private">🔒 Privado</option>
                            <option value="public">🌍 Público</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Color Personalizado</label>
                        <div class="flex items-center h-[42px] border border-gray-300 rounded-lg overflow-hidden px-1 bg-white">
                            <input type="color" name="color" id="eColor" class="h-8 w-full border-none cursor-pointer bg-transparent" value="#6B7280">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Descripción</label>
                    <textarea name="description" id="eDesc" rows="3" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none" placeholder="Detalles del evento..."></textarea>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center shrink-0 rounded-b-2xl">
            <button type="button" id="btnDeleteModal" class="hidden text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-2 rounded-lg font-bold text-xs uppercase tracking-wide flex items-center transition">
                <i class="ph-bold ph-trash text-lg mr-1"></i> Eliminar
            </button>
            <div class="flex gap-3 ml-auto">
                <button type="button" onclick="closeFormModal()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition shadow-sm text-sm">Cancelar</button>
                <button type="submit" form="eventForm" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md transition transform hover:-translate-y-0.5 text-sm flex items-center gap-2">
                    <i class="ph-bold ph-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>