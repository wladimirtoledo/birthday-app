// Shared event preview utilities
(function(window){
    function hexToRgba(hex, alpha) {
        try{
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }catch(e){ return `rgba(107,114,128,${alpha})`; }
    }

    // Expose hexToRgba
    window.hexToRgba = window.hexToRgba || hexToRgba;

    // getCardHTML: shared card generator used by previews and renderers
    if (typeof window.getCardHTML !== 'function') {
        window.getCardHTML = function(cardMode, color, icon, name, ev) {
            let innerStyle = '', innerText = 'text-gray-700', innerIconColor = color;
            let contentHTML = `<i class="ph ph-${icon}" style="color:${innerIconColor}"></i> <span style="${innerText}">${name}</span>`;

            if (cardMode === 'block') { innerStyle=`background:#fff; border-left:3px solid ${color}; box-shadow:0 1px 2px rgba(0,0,0,0.05);`; }
            else if (cardMode === 'subtle') { innerStyle=`background:${hexToRgba(color,0.15)}; color:${color}; border-left:3px solid ${color}; font-weight:600;`; innerText = `color:${color}`; contentHTML = `<i class="ph ph-${icon}" style="color:${color}"></i> <span style="color:${color}">${name}</span>`; }
            else if (cardMode === 'gradient') { innerStyle=`background:linear-gradient(135deg,${color},#ffffff 180%); color:white; border:1px solid ${color};`; innerText = `color:white; text-shadow:0 1px 2px rgba(0,0,0,0.3);`; contentHTML = `<i class="ph ph-${icon}" style="color:white"></i> <span style="color:white; text-shadow:0 1px 2px rgba(0,0,0,0.3);">${name}</span>`; }
            else if (cardMode === 'important') { innerStyle=`background:${color}; color:white; font-weight:bold; box-shadow:0 2px 4px rgba(0,0,0,0.15); border: 1px solid rgba(0,0,0,0.1); border-left: 3px solid rgba(0,0,0,0.3);`; contentHTML = `<i class="ph-fill ph-${icon}" style="color:white"></i> <span style="color:white">${name}</span>`; }
            else if (cardMode === 'transparent') { innerStyle=`background:transparent; border:1px dashed ${color}; color:${color}; font-weight:500;`; innerText = `color:${color}`; contentHTML = `<i class="ph ph-${icon}" style="color:${color}"></i> <span style="color:${color}">${name}</span>`; }

            else if (cardMode === 'photo') {
                innerStyle = `background: #f8fafc; border-left: 2px solid ${color}; padding: 0 4px; display: flex; align-items: center; overflow: hidden; box-shadow: none; min-height: 28px;`;
                if (ev && ev.type_slug === 'birthday' && ev.image_url) {
                    // Calcular edad
                    let edad = '';
                    if (ev.birthdate && ev.start) {
                        const birthYear = String(ev.birthdate).slice(0,4);
                        const eventYear = String(ev.start).slice(0,4);
                        if (birthYear && eventYear) {
                            edad = (parseInt(eventYear)-parseInt(birthYear)).toString();
                        }
                    }
                    // Solo primer nombre y primer apellido
                    let firstName = '';
                    let lastName = '';
                    if (ev.title) {
                        const parts = ev.title.trim().split(' ');
                        firstName = parts[0] || '';
                        lastName = parts.length > 1 ? parts[1] : '';
                    }
                    // Mostrar edad debajo del nombre si el año no es 1000
                    let edadHtml = '';
                    if (ev.birthdate && ev.start) {
                        const birthYear = String(ev.birthdate).slice(0,4);
                        if (birthYear !== '1000' && edad) {
                            edadHtml = `<div style='color:#6366f1;font-weight:bold;font-size:10px;line-height:1;'>${edad} años</div>`;
                        }
                    }
                    contentHTML = `<img src="${ev.image_url}" alt="foto" style="width: 18px; height: 18px; border-radius: 6px; object-fit: cover; margin-right: 6px; flex-shrink: 0;">` +
                        `<span style="padding: 0 2px; font-size: 11px; color: #374151; font-weight: 500; display:block;">${firstName} ${lastName}</span>` + edadHtml;
                } else if (ev && ev.image_url) {
                    contentHTML = `<img src="${ev.image_url}" alt="foto" style="width: 18px; height: 18px; border-radius: 6px; object-fit: cover; margin-right: 6px; flex-shrink: 0;">` +
                        `<span style="padding: 0 2px; font-size: 11px; color: #374151; font-weight: 500;">${name}</span>`;
                } else {
                    contentHTML = `<div style="width: 18px; height: 18px; background-color: #f3f4f6; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 6px;"><i class="ph-fill ph-image text-[10px] text-gray-400"></i></div><span style="padding: 0 2px; font-size: 11px; color: #374151; font-weight: 500;">${name}</span>`;
                }
            }

            else if (cardMode === 'detailed') {
                innerStyle = `background: white; border-radius: 5px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); border: 1px solid #f1f5f9; border-left: 3px solid ${color}; padding: 2px 4px; display: flex; align-items: center; gap: 4px; margin: 1px 0; min-height: 26px;`;
                const bgIcon = hexToRgba(color, 0.13);
                if (ev && ev.type_slug === 'birthday' && ev.image_url) {
                    // Calcular edad
                    let edad = '';
                    if (ev.birthdate && ev.start) {
                        const birthYear = String(ev.birthdate).slice(0,4);
                        const eventYear = String(ev.start).slice(0,4);
                        if (birthYear && eventYear) {
                            edad = (parseInt(eventYear)-parseInt(birthYear)).toString();
                        }
                    }
                    // Solo primer nombre y primer apellido
                    let firstName = '';
                    let lastName = '';
                    if (ev.title) {
                        const parts = ev.title.trim().split(' ');
                        firstName = parts[0] || '';
                        lastName = parts.length > 1 ? parts[1] : '';
                    }
                    // Mostrar edad debajo del nombre si el año no es 1000
                    let edadHtml = '';
                    if (ev.birthdate && ev.start) {
                        const birthYear = String(ev.birthdate).slice(0,4);
                        if (birthYear !== '1000' && edad) {
                            edadHtml = `<div style='color:#6366f1;font-weight:bold;font-size:10px;line-height:1;'>${edad} años</div>`;
                        }
                    }
                    contentHTML = `
                        <img src="${ev.image_url}" alt="foto" style="width: 22px; height: 22px; border-radius: 50%; object-fit: cover; margin-right: 6px; flex-shrink: 0; border:2px solid ${color};">
                        <div style="display: flex; flex-direction: column; overflow: hidden; justify-content: center;">
                            <span style="font-weight: 700; font-size: 10px; color: #1e293b; line-height: 1.1;">${firstName} ${lastName}</span>
                            ${edadHtml}
                            <span style="font-size: 8px; color: #64748b; margin-top: 0;">Cumpleaños</span>
                        </div>
                    `;
                } else {
                    contentHTML = `
                        <div style="width: 18px; height: 18px; border-radius: 50%; background-color: ${bgIcon}; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: ${color}; box-shadow: inset 0 0 0 1px ${color}20;">
                            <i class="ph-fill ph-${icon}" style="font-size:12px;"></i>
                        </div>
                        <div style="display: flex; flex-direction: column; overflow: hidden; justify-content: center;">
                            <span style="font-weight: 700; font-size: 10px; color: #1e293b; line-height: 1.1;">${name}</span>
                            <span style="font-size: 8px; color: #64748b; margin-top: 0;">Detalles...</span>
                        </div>
                    `;
                }
            }

            return `
                <div class="w-full ${cardMode!=='photo'&&cardMode!=='detailed'?'p-1.5':''} rounded text-[7px] truncate flex items-center gap-1 transition-transform transform hover:scale-[1.02] cursor-pointer shadow-sm mb-1" style="${innerStyle}">
                    ${contentHTML}
                </div>`;
        };
    }

    // getCalendarCellHTML: reproduce exact HTML used in event_types.php 'Resultado en Calendario'
    if (typeof window.getCalendarCellHTML !== 'function') {
        window.getCalendarCellHTML = function(calMode, cardMode, color, icon, name, ev) {
            let nm;
            let html;
            // Unificar tarjeta de cumpleaños en todos los modos visuales
            if (ev && ev.type === 'birthday') {
                const c = color || '#4F46E5';
                let nombre = ev.title || '';
                let edad = (ev.age && !isNaN(ev.age)) ? ` (${ev.age})` : '';
                let foto = ev.image_url ? `<img src='${ev.image_url}' alt='avatar' style='width:22px;height:22px;border-radius:50%;object-fit:cover;display:inline-block;margin-right:6px;vertical-align:middle;border:2px solid #EC4899;'>` : '';
                nm = `${foto}<span style='font-weight:700;color:#1e293b;'>${nombre}${edad}</span>`;
                // Tarjeta compacta y visualmente consistente
                html = `<div class=\"w-full p-1.5 rounded text-[7px] truncate flex items-center gap-2 transition-transform transform hover:scale-[1.02] cursor-pointer shadow-sm mb-1\" style=\"background:#fff; border-left:3px solid ${c}; box-shadow:0 1px 2px rgba(0,0,0,0.05);\">${nm}</div>`;
                return html;
            } else {
                if (calMode === 'badge' && ev && ev.title) {
                    nm = ev.title;
                } else {
                    nm = name || (ev && (ev.type_name || ev.title)) || 'Tipo';
                }
            }
            const cm = cardMode || 'block';
            const c = color || '#4F46E5';
            const ic = icon || 'circle';

            // Helper local
            const bgRgba = (col, a) => {
                try { let r=parseInt(col.slice(1,3),16), g=parseInt(col.slice(3,5),16), b=parseInt(col.slice(5,7),16); return `rgba(${r},${g},${b},${a})`; } catch(e){ return `rgba(79,70,229,${a})`; }
            };

            html = '';
            // compute day label/number from ev when available
            let dayLabel = 'MIÉ';
            let dayNumber = '24';
            try {
                if (ev && (ev.start || ev.event_date)) {
                    const d = new Date(ev.start || ev.event_date);
                    dayLabel = d.toLocaleDateString('es-ES', { weekday: 'short' }).toUpperCase().replace('.','');
                    dayNumber = String(d.getDate());
                }
            } catch(e){}
            // If rendering inside the real calendar, FullCalendar already shows the day number.
            // Avoid duplicating the date header when `ev.isCalendar` is truthy.
            const showDateHeader = !(ev && ev.isCalendar);

            if (calMode === 'default' || calMode === 'point' || calMode === undefined) {
                const cardHTML = window.getCardHTML ? window.getCardHTML(cm, c, ic, nm, ev) : '';
                html = `${cardHTML}`;
            } else if (calMode === 'badge') {
                // Badge: mismo tamaño visual que banner
                html = `<div class="h-4 w-full flex items-center justify-between px-1 shadow-sm z-10" style="background-color:${c}"><span class="text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1"><i class="ph-fill ph-${ic}" style="vertical-align:middle; font-size:8px;"></i> ${nm}</span></div>`;
            } else if (calMode === 'background') {
                // Si es feriado, usar diseño idéntico a event_types.php preview (background)
                if (ev && (ev.type_slug === 'holiday' || ev.slug === 'holiday')) {
                    const bgRgba = window.hexToRgba ? window.hexToRgba(c, 0.15) : c;
                    html = `
                        <div class="w-full h-full p-2 flex flex-col justify-between relative overflow-hidden" style="background-color: ${bgRgba};">
                            <div class="flex justify-between items-start z-10 relative">
                                <span class="text-[8px] font-black opacity-60" style="color:${c}">MIÉ</span>
                                <span class="text-sm font-black" style="color:${c}">24</span>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-20 pointer-events-none"><i class="ph ph-calendar-blank text-4xl transform -rotate-12" style="color:${c}"></i></div>
                            <div class="absolute bottom-1 right-1 text-[6px] font-bold uppercase opacity-80 pointer-events-none" style="color: ${c}">${nm}</div>
                        </div>
                    `;
                } else {
                    // Fondo completo: solo el fondo y el icono/label, sin wrappers
                    html = `<div class="absolute inset-0 flex items-center justify-center opacity-20 pointer-events-none"><i class="ph ph-${ic} text-4xl transform -rotate-12" style="color:${c}"></i></div><div class="absolute bottom-1 right-1 text-[6px] font-bold uppercase opacity-80 pointer-events-none" style="color: ${c}">${nm}</div>`;
                }
            } else if (calMode === 'banner') {
                // Banner: solo la cinta, sin wrappers
                html = `<div class="h-4 w-full flex items-center justify-between px-1 shadow-sm z-10" style="background-color:${c}"><span class="text-[6px] font-bold text-white uppercase tracking-wider flex items-center gap-1"><i class="ph-bold ph-${ic}"></i> ${nm}</span></div>`;
            } else {
                // Fallback to card
                const cardHTML = window.getCardHTML ? window.getCardHTML(cm, c, ic, nm) : '';
                html = cardHTML;
            }

            return html;
        };
    }

})(window);
