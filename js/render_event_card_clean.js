// render_event_card.js
(function(window){
    function hexToRgba(hex, alpha) {
        try{
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }catch(e){return `rgba(107,114,128,${alpha})`;}
    }

    function renderEventCard(ev){
        ev = ev || {};
        const evDate = new Date(ev.start || ev.event_date || Date.now());
        const dayNum = evDate.getDate();
        const monthName = evDate.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase().replace('.','');
        const mode = (ev.display_mode || 'block').toLowerCase();
        const color = ev.color || ev.type_color || '#6B7280';
        const rawTitle = String(ev.title || 'Evento');
        const title = rawTitle.replace('⏳ ','').replace('🎂 ','');
        const icon = ev.icon || 'circle';
        const isBirthday = ev.type === 'birthday' || ev.type === 'birthday';
        const canEdit = !!ev.editable;
        const editBtn = canEdit ? `<button onclick="(function(){ try{ if(window.openEditModal) return openEditModal(${JSON.stringify(ev.id)}); if(window.openFormModal){ window.currentEventData = Object.assign(window.currentEventData||{}, { id: ${JSON.stringify(ev.id)}, title: ${JSON.stringify(ev.title||'')}, event_date: ${JSON.stringify(ev.event_date||ev.start||'')}, raw_end_date: ${JSON.stringify(ev.raw_end_date||'')}, raw_start_time: ${JSON.stringify(ev.raw_start_time||'')}, raw_end_time: ${JSON.stringify(ev.raw_end_time||'')} }); return openFormModal(); } }catch(e){} })()" class="ml-2 text-gray-500 hover:text-indigo-600 p-1.5 bg-white rounded-lg transition shadow-sm border border-gray-100" title="Modificar"><i class="ph-bold ph-pencil-simple text-lg"></i></button>` : '';
        const authorBadge = (ev.creator_name && !isBirthday) ? `<div class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-gray-100 flex items-center"><i class="ph-bold ph-user mr-1"></i> ${ev.creator_name}</div>` : '';
        const hasImage = !!ev.image_url;

        // Si existe `getCardHTML` compartida (exportada desde event_preview.js), usarla como fuente única de verdad.
        try {
            if (typeof window.getCardHTML === 'function') {
                const shared = window.getCardHTML(mode, color, icon, title, ev);
                if (typeof shared === 'string' && shared.trim()) return shared;
            }
        } catch(e) { /* ignore and continue to local fallback */ }

        let html = '';
        switch(mode){
            case 'detailed':
                html = `\n                <div class="fade-in mb-3 group bg-white rounded-lg shadow-sm border border-gray-100 relative overflow-hidden flex items-center gap-3 p-3" style="border-left:4px solid ${color};">\n                    <div class="flex-shrink-0 w-12 h-12 rounded-${isBirthday?'full':'lg'} overflow-hidden">\n                        ${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full flex items-center justify-center text-white" style="background:${color}"><i class="ph-fill ph-${icon}"></i></div>` }\n                    </div>\n                    <div class="flex-1 min-w-0">\n                        <h3 class="font-bold text-sm text-gray-800 truncate">${title}</h3>\n                        ${isBirthday && ev.age ? `<div class="text-xs text-indigo-600 font-bold mt-1">🎂 ${ev.age} años</div>` : ''}\n                        <div class="flex items-center justify-between mt-1">\n                            <span class="text-[10px] text-gray-500">${ev.type_name || ''}</span>\n                            <span class="text-[10px] text-gray-400">${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</span>\n                        </div>\n                        ${ ev.description ? `<p class="text-xs text-gray-600 mt-2 line-clamp-2">${ev.description}</p>` : '' }\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'photo':
                html = `\n                <div class="fade-in group flex items-center bg-white rounded-lg border border-gray-100 p-2 shadow-sm" style="border-left:3px solid ${color};">\n                    <div class="w-10 h-10 overflow-hidden rounded-md flex-shrink-0">${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400"><i class="ph-fill ph-image"></i></div>` }</div>\n                    <div class="ml-3 flex-1 min-w-0">\n                        <div class="flex items-center justify-between">\n                            <div class="font-bold text-sm truncate">${title}</div>\n                            <div class="text-[10px] text-gray-400">${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</div>\n                        </div>\n                        ${isBirthday && ev.age ? `<div class="text-xs text-indigo-600 font-bold mt-1">🎂 ${ev.age} años</div>` : ''}\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'banner':
                html = `\n                <div class="fade-in group rounded-lg overflow-hidden relative mb-3 bg-white border border-gray-100 shadow-sm">\n                    <div class="h-8 w-full flex items-center justify-between px-3 py-1" style="background-color:${color}">\n                        <span class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2"><i class="ph-bold ph-${icon}"></i> ${title}</span>\n                        <span class="text-[10px] text-white/80">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</span>\n                    </div>\n                    <div class="p-3">\n                        <div class=\\"text-sm text-gray-700\\">${ev.description ? ev.description.substring(0,120) : ''}</div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'important':
                html = `\n                <div class="fade-in group rounded-xl overflow-hidden relative mb-3">\n                    ${ hasImage ? `<div class="h-36 w-full overflow-hidden"><img src="${ev.image_url}" class="w-full h-full object-cover"></div>` : `<div class="h-24 w-full" style="background:${color}"></div>` }\n                    <div class="absolute left-4 bottom-4 text-white">\n                        <h3 class="font-extrabold text-lg leading-tight">${title}</h3>\n                        <div class="text-sm opacity-90">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'badge':
                html = `\n                <div class="fade-in group bg-white rounded-lg mb-3 shadow-sm border border-gray-100 w-full" style="border-left:4px solid ${color};">\n                    <div class="p-3 flex items-center gap-3">\n                        <span class="badge-responsive flex-shrink-0 flex items-center justify-center text-[12px] font-bold px-3 py-1 rounded-md" style="background: ${hexToRgba(color,0.08)}; color: ${color};"><i class="ph-bold ph-${icon}"></i></span>\n                        <div class="text-sm font-medium whitespace-normal">${title}</div>\n                        ${editBtn}\n                    </div>\n                </div>`;
                break;
            case 'gradient':
                html = `\n                <div class="fade-in group rounded-xl p-3 mb-3 text-white" style="background: linear-gradient(135deg, ${color}, #ffffff33);">\n                    <div class="flex items-start gap-3">\n                        <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0">${ hasImage ? `<img src="${ev.image_url}" class="w-full h-full object-cover">` : `<div class="w-full h-full" style="background:rgba(255,255,255,0.12)"></div>` }</div>\n                        <div class="flex-1 min-w-0">\n                            <h3 class="font-bold text-sm leading-tight">${title}</h3>\n                            <div class="text-[10px] opacity-90">${ev.type_name || ''}</div>\n                        </div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'subtle':
                html = `\n                <div class="fade-in group bg-white rounded-xl p-3 mb-3 border border-gray-100" style="background:${hexToRgba(color, 0.06)}; border-left:4px solid ${color};">\n                    <div class="flex items-center gap-3">\n                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white" style="background:${color}"><i class="ph-fill ph-${icon}"></i></div>\n                        <div class="flex-1 min-w-0">\n                            <h3 class="font-bold text-sm truncate">${title}</h3>\n                            <div class="text-[10px] text-gray-500">${ev.type_name || ''}</div>\n                        </div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'compact':
                html = `\n                <div class="fade-in group bg-white rounded-lg p-2 mb-2 border border-gray-100 shadow-sm" style="border-left:3px solid ${color};">\n                    <div class="flex items-center gap-2">\n                        <div class="w-6 h-6 rounded flex items-center justify-center text-white text-xs" style="background:${color}"><i class="ph-bold ph-${icon}"></i></div>\n                        <div class="flex-1 min-w-0">\n                            <h4 class="font-bold text-xs truncate">${title}</h4>\n                            <div class="text-[9px] text-gray-400">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {weekday:'short', day:'numeric'})}</div>\n                        </div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'outline':
                html = `\n                <div class="fade-in group bg-white rounded-xl p-3 mb-3 border-2 border-dashed" style="border-color:${color}; color:${color};">\n                    <div class="flex items-center gap-3">\n                        <div class="w-10 h-10 rounded-lg flex items-center justify-center border-2" style="border-color:${color}; color:${color}"><i class="ph ph-${icon}"></i></div>\n                        <div class="flex-1 min-w-0">\n                            <h3 class="font-bold text-sm truncate">${title}</h3>\n                            <div class="text-[10px] opacity-80">${ev.type_name || ''}</div>\n                        </div>\n                    </div>\n                    ${editBtn}\n                </div>`;
                break;
            case 'background':
                html = `\n                <div class="fade-in group rounded-xl p-4 mb-3 text-center text-white" style="background:${color};">\n                    <div class="text-lg font-extrabold">${title}</div>\n                    <div class="text-xs opacity-90 mt-1">${ev.type_name || ''} • ${new Date(ev.start).toLocaleDateString('es-ES', {day:'numeric', month:'short'})}</div>\n                    ${editBtn}\n                </div>`;
                break;
            default:
                if (ev.isCalendar) {
                    // Compact card for calendar cells: avoid duplicating the day number (FullCalendar already shows it).
                    html = `\n                <div class="p-1 rounded shadow-sm border border-gray-100 hover:shadow-md transition group relative flex flex-col mb-1 fade-in" style="background:#fff; border-left:3px solid ${color}; min-width: 60px;">\n                    <div class="flex-1 min-w-0 text-left">\n                        <h3 class="font-bold text-xs leading-tight line-clamp-2">${title}</h3>\n                        <div class="w-full flex justify-start mt-1">\n                            <span class="badge-responsive flex items-center justify-center text-[9px] font-bold px-2 py-1 rounded uppercase tracking-wider" style="background: ${hexToRgba(color,0.08)}; color: ${color};">\n                                <i class="ph-bold ph-${icon}"></i> ${ev.type_name || 'Evento'}\n                            </span>\n                        </div>\n                        ${isBirthday && ev.age ? `<div class="text-[9px] font-bold px-1 py-0.5 rounded bg-indigo-50 text-indigo-700 mt-1">🎂 ${ev.age}</div>` : ''}\n                    </div>\n                    ${editBtn}\n                </div>`;
                } else {
                    html = `\n                <div class="p-3 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition group relative flex flex-col mb-3 fade-in" style="background:#fff; border-left:4px solid ${color};">\n                    ${ hasImage ? `<div class="h-24 w-full rounded-lg mb-2 overflow-hidden bg-gray-100"><img src="${ev.image_url}" class="w-full h-full object-cover"></div>` : '' }\n                    <div class="flex gap-3 mb-1">\n                        <div class="flex flex-col items-center justify-center bg-white border border-gray-100 rounded-lg w-10 h-12 shadow-sm shrink-0">\n                            <span class="text-[9px] font-bold text-gray-400 uppercase leading-none mt-1">${monthName}</span>\n                            <span class="text-3xl font-black text-gray-800 leading-none">${dayNum}</span>\n                        </div>\n                        <div class="flex-1 min-w-0">\n                            <div class="flex justify-between items-start">\n                                <h3 class="font-bold text-sm leading-tight line-clamp-2">${title}</h3>\n                            </div>\n                            <div class="flex items-center mt-1 w-full">\n                                <span class="badge-responsive text-[9px] font-bold px-2 py-1 rounded uppercase tracking-wider flex items-center gap-2" style="background: ${hexToRgba(color,0.08)}; color: ${color};">\n                                    <i class="ph-bold ph-${icon}"></i> ${ev.type_name || 'Evento'}\n                                </span>\n                                ${isBirthday && ev.age ? `<span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 ml-2">🎂 ${ev.age}</span>` : ''}\n                            </div>\n                        </div>\n                    </div>\n                    ${ ev.description ? `<p class="text-xs opacity-80 mt-1 line-clamp-2">${ev.description}</p>` : '' }\n                    ${editBtn}\n                    ${authorBadge}\n                </div>`;
                }
        }
        return html;
    }

    window.renderEventCard = renderEventCard;
})(window);
