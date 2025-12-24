// render_event_card.js
(function(window){
    function hexToRgba(hex, alpha) {
        try{
            let r=parseInt(hex.slice(1,3),16), g=parseInt(hex.slice(3,5),16), b=parseInt(hex.slice(5,7),16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }catch(e){return `rgba(107,114,128,${alpha})`;}
    }

    function renderEventCard(ev){
        // Siempre delegar en getCardHTML (fuente de verdad visual)
        const mode = (ev && ev.display_mode) ? ev.display_mode : 'block';
        const color = (ev && (ev.color || ev.type_color)) ? (ev.color || ev.type_color) : '#6B7280';
        const icon = (ev && ev.icon) ? ev.icon : 'circle';
        const title = (ev && ev.title) ? String(ev.title).replace('⏳ ','').replace('🎂 ','') : 'Evento';
        if (typeof window.getCardHTML === 'function') {
            return window.getCardHTML(mode, color, icon, title, ev);
        }
        return '';
    }

    window.renderEventCard = renderEventCard;
})(window);
