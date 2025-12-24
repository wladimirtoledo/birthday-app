<?php
// profile.php
require_once 'includes/functions.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100 font-sans h-screen flex flex-col overflow-hidden text-slate-800">
    
    <div class="shrink-0 bg-white shadow-sm z-20 relative">
        <?php include 'includes/navbar.php'; ?>
    </div>

    <!-- LOADER DE PÁGINA -->
    <div id="pageLoader" class="flex-1 flex flex-col items-center justify-center min-h-0">
        <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-200 border-t-indigo-600"></div>
        <p class="mt-4 text-indigo-600 font-bold text-sm tracking-widest animate-pulse">CARGANDO PERFIL...</p>
    </div>

    <!-- CONTENIDO (Oculto al inicio) -->
    <main id="mainContent" class="hidden flex-1 max-w-7xl mx-auto w-full py-8 px-4 min-h-0 flex-col overflow-y-auto custom-scroll">
        
        <div class="flex items-center gap-3 mb-8 shrink-0">
            <div class="bg-indigo-600 p-2.5 rounded-xl text-white shadow-lg shadow-indigo-200">
                <i class="ph-bold ph-user-gear text-2xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mi Perfil</h1>
                <p class="text-sm text-gray-500">Administra tu información personal pública y privada.</p>
            </div>
        </div>

        <form id="profileForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 pb-10">
            
            <!-- COLUMNA IZQUIERDA (Avatar, Bio, Redes) -->
            <div class="lg:col-span-4 space-y-6">
                
                <!-- 1. AVATAR NO INVASIVO -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 flex flex-col items-center relative overflow-hidden">
                    <div class="relative group">
                        <img id="avatarPreview" src="https://via.placeholder.com/150" class="w-40 h-40 rounded-full object-cover border-4 border-white shadow-xl">
                        
                        <!-- Botón Flotante de Edición -->
                        <button type="button" onclick="document.getElementById('avatarFile').click()" 
                                class="absolute bottom-1 right-1 bg-indigo-600 text-white p-2.5 rounded-full shadow-lg hover:bg-indigo-700 transition transform hover:scale-110 border-2 border-white"
                                title="Cambiar Foto">
                            <i class="ph-bold ph-camera text-lg"></i>
                        </button>
                        
                        <input type="file" name="avatar" id="avatarFile" class="hidden" accept="image/*">
                    </div>

                    <!-- Input URL Discreto -->
                    <div class="mt-6 w-full relative">
                         <i class="ph-bold ph-link absolute left-3 top-2.5 text-gray-400"></i>
                        <input type="url" name="avatar_url_input" id="avatarUrl" placeholder="O pega una URL de imagen..." class="w-full pl-9 pr-3 py-2 text-xs border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-600 bg-gray-50">
                    </div>

                    <div class="text-center mt-4">
                        <h2 id="displayName" class="text-xl font-bold text-gray-900">Cargando...</h2>
                        <p id="displayNick" class="text-sm text-indigo-500 font-medium">@...</p>
                    </div>
                </div>
                
                <!-- 2. PRESENTACIÓN -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <h3 class="font-bold text-gray-800 mb-3 text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="ph-duotone ph-quotes text-lg text-indigo-500"></i> Sobre Mí
                    </h3>
                    <textarea name="bio" id="bio" rows="4" class="w-full border border-gray-300 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none bg-gray-50 focus:bg-white transition" placeholder="Escribe una breve presentación..."></textarea>
                </div>

                <!-- 3. REDES SOCIALES -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider flex items-center gap-2">
                            <i class="ph-duotone ph-share-network text-lg text-indigo-500"></i> Redes
                        </h3>
                        <button type="button" onclick="addSocialRow()" class="text-[10px] bg-indigo-50 text-indigo-700 px-2 py-1 rounded-md font-bold hover:bg-indigo-100 transition border border-indigo-100 uppercase">+ Agregar</button>
                    </div>
                    <div id="socialContainer" class="space-y-3"></div>
                    <input type="hidden" name="social_links_json" id="socialLinksJson">
                </div>
            </div>

            <!-- COLUMNA DERECHA (Datos, Geo, Seguridad) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- 4. INFORMACIÓN PERSONAL -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <h3 class="font-bold text-gray-900 mb-6 text-lg border-b border-gray-100 pb-3 flex items-center gap-2">
                        <i class="ph-duotone ph-identification-card text-indigo-500"></i> Datos Personales
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Nickname (Único)</label>
                            <input type="text" name="nickname" id="nickname" required class="w-full border border-gray-300 p-2.5 rounded-lg text-sm font-bold text-gray-700 bg-gray-50 focus:bg-white transition uppercase" oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,'')">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Email</label>
                            <input type="email" name="email" id="email" required class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Nombres</label>
                            <input type="text" name="first_name" id="firstName" required class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Apellidos</label>
                            <input type="text" name="last_name" id="lastName" required class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>

                        <!-- Fecha de Nacimiento (3 Selects) -->
                        <div class="md:col-span-2 bg-indigo-50/50 p-4 rounded-xl border border-indigo-100">
                            <label class="block text-xs font-bold text-indigo-800 mb-2 uppercase">Fecha de Nacimiento</label>
                            <div class="flex gap-3">
                                <select id="dobDay" class="border border-indigo-200 p-2 rounded-lg text-sm w-20 bg-white focus:ring-2 focus:ring-indigo-500"><option value="">Día</option><?php for($i=1;$i<=31;$i++)echo"<option value='$i'>$i</option>";?></select>
                                <select id="dobMonth" class="border border-indigo-200 p-2 rounded-lg text-sm flex-1 bg-white focus:ring-2 focus:ring-indigo-500"><option value="">Mes</option><option value="1">Enero</option><option value="2">Febrero</option><option value="3">Marzo</option><option value="4">Abril</option><option value="5">Mayo</option><option value="6">Junio</option><option value="7">Julio</option><option value="8">Agosto</option><option value="9">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option></select>
                                <input type="number" id="dobYear" placeholder="Año (Opcional)" class="border border-indigo-200 p-2 rounded-lg text-sm w-32 focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <input type="hidden" name="birthdate" id="finalBirthdate">
                        </div>

                        <div><label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Teléfono</label><input type="text" name="phone" id="phone" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></div>
                        <div><label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Género</label><select name="gender" id="gender" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500 outline-none"><option value="">Seleccionar...</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option><option value="Otro">Otro</option><option value="Prefiero no decir">Prefiero no decir</option></select></div>
                    </div>
                </div>

                <!-- 5. UBICACIÓN -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
                    <h3 class="font-bold text-gray-900 mb-6 text-lg border-b border-gray-100 pb-3 flex items-center gap-2">
                        <i class="ph-duotone ph-map-pin text-indigo-500"></i> Ubicación
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div><label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">País</label><select name="country" id="country" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500"><option value="">Cargando...</option></select></div>
                        <div><label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Provincia / Región</label><select name="region" id="region" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500" disabled><option value="">Seleccione país</option></select></div>
                        <div><label class="block text-xs font-bold text-gray-600 mb-1.5 uppercase">Ciudad / Comuna</label><select name="city" id="city" class="w-full border border-gray-300 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-indigo-500" disabled><option value="">Seleccione provincia</option></select></div>
                    </div>
                </div>

                <!-- 6. SEGURIDAD -->
                <div class="bg-red-50 rounded-2xl border border-red-100 p-8">
                    <h3 class="font-bold text-red-800 mb-6 text-lg flex items-center gap-2">
                        <i class="ph-duotone ph-shield-warning text-red-500"></i> Seguridad
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div><label class="block text-xs font-bold text-red-700 mb-1.5 uppercase">Nueva Contraseña</label><input type="password" name="password" id="pass1" placeholder="Dejar en blanco para mantener actual" class="w-full border border-red-200 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-red-500 outline-none"></div>
                        <div><label class="block text-xs font-bold text-red-700 mb-1.5 uppercase">Confirmar Contraseña</label><input type="password" id="pass2" placeholder="Repite la contraseña" class="w-full border border-red-200 p-2.5 rounded-lg text-sm bg-white focus:ring-2 focus:ring-red-500 outline-none"></div>
                    </div>
                </div>

                <!-- Footer Acciones -->
                <div class="flex justify-end pt-4 pb-12">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3.5 rounded-xl shadow-lg shadow-indigo-200 font-bold text-sm transition transform hover:-translate-y-1 flex items-center gap-2">
                        <i class="ph-bold ph-floppy-disk text-lg"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </main>

    <div class="shrink-0 mt-auto bg-white border-t"><?php include 'includes/footer.php'; ?></div>

    <script>
        const API_URL = 'public/api/users.php'; // USAR API MODULAR

        // --- AVATAR PREVIEW ---
        document.getElementById('avatarFile').addEventListener('change', e=>{
            if(e.target.files[0]){
                const r=new FileReader();
                r.onload=ev=>document.getElementById('avatarPreview').src=ev.target.result;
                r.readAsDataURL(e.target.files[0]);
                document.getElementById('avatarUrl').value='';
            }
        });
        document.getElementById('avatarUrl').addEventListener('input', e=>{
            if(e.target.value.length>8) document.getElementById('avatarPreview').src=e.target.value;
        });

        // --- REDES SOCIALES ---
        function addSocialRow(k='',v=''){
            const div = document.createElement('div');
            div.className = "flex gap-2 items-center animate-fade-in";
            div.innerHTML = `
                <div class="relative w-1/3">
                    <i class="ph-bold ph-caret-down absolute right-2 top-3 text-xs text-gray-400 pointer-events-none"></i>
                    <select class="s-key appearance-none w-full border border-gray-300 p-2 rounded-lg text-xs bg-gray-50 focus:ring-2 focus:ring-indigo-500 outline-none font-medium text-gray-700">
                        <option value="steam">Steam</option><option value="discord">Discord</option>
                        <option value="facebook">Facebook</option><option value="instagram">Instagram</option>
                        <option value="spotify">Spotify</option><option value="twitter">X (Twitter)</option>
                        <option value="linkedin">LinkedIn</option><option value="other">Otro</option>
                    </select>
                </div>
                <input type="text" class="s-val border border-gray-300 p-2 rounded-lg text-xs w-full focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Usuario o URL" value="${v}">
                <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 p-2 hover:bg-red-50 rounded-lg transition"><i class="ph-bold ph-trash text-lg"></i></button>
            `;
            document.getElementById('socialContainer').appendChild(div);
            if(k) div.querySelector('.s-key').value = k;
        }

        // --- GEO DATA ---
        async function loadGeo(type, parentId, targetId) {
            const sel = document.getElementById(targetId);
            sel.innerHTML = '<option value="">Cargando...</option>';
            sel.disabled = true;
            try {
                const res = await fetch(`${API_URL}?action=get_geo_data&type=${type}&parent_id=${parentId||''}`);
                const data = await res.json();
                sel.innerHTML = '<option value="">Seleccione...</option>';
                data.forEach(i => {
                    const opt = document.createElement('option');
                    opt.value = i.id; 
                    opt.text = i.name;
                    sel.appendChild(opt);
                });
                sel.disabled = false;
            } catch(e) { sel.innerHTML = '<option>Error</option>'; }
        }

        document.getElementById('country').addEventListener('change', function() {
            loadGeo('regions', this.value, 'region');
            document.getElementById('city').innerHTML = '<option value="">Seleccione provincia</option>';
            document.getElementById('city').disabled = true;
        });
        document.getElementById('region').addEventListener('change', function() {
            loadGeo('cities', this.value, 'city');
        });

        // --- CARGAR DATOS ---
        async function loadProfile() {
            document.getElementById('pageLoader').classList.remove('hidden');
            document.getElementById('mainContent').classList.add('hidden');
            
            try {
                // 1. Países
                await loadGeo('countries', null, 'country');

                // 2. Datos Usuario
                const res = await fetch(`${API_URL}?action=get_profile`);
                const u = await res.json();
                
                if (!u.id) throw new Error("Error de sesión");

                document.getElementById('nickname').value = u.nickname;
                document.getElementById('firstName').value = u.first_name;
                document.getElementById('lastName').value = u.last_name;
                document.getElementById('email').value = u.email;
                document.getElementById('bio').value = u.bio || '';
                document.getElementById('phone').value = u.phone || '';
                document.getElementById('gender').value = u.gender || '';

                // Fecha
                if(u.birthdate){
                    const [y,m,d] = u.birthdate.split('-');
                    document.getElementById('dobDay').value=parseInt(d);
                    document.getElementById('dobMonth').value=parseInt(m);
                    document.getElementById('dobYear').value=(y==='1000')?'':y;
                }

                // Geo Cascada
                if (u.country) {
                    document.getElementById('country').value = u.country;
                    await loadGeo('regions', u.country, 'region');
                    if (u.region) {
                        document.getElementById('region').value = u.region;
                        await loadGeo('cities', u.region, 'city');
                        if (u.city) document.getElementById('city').value = u.city;
                    }
                }

                // Visuales
                document.getElementById('displayName').innerText = `${u.first_name} ${u.last_name}`;
                document.getElementById('displayNick').innerText = `@${u.nickname}`;
                document.getElementById('avatarPreview').src = u.avatar_url || `https://ui-avatars.com/api/?name=${u.first_name}+${u.last_name}&background=random&color=fff&size=200`;

                // Redes
                document.getElementById('socialContainer').innerHTML='';
                if(u.social_links) Object.entries(u.social_links).forEach(([k,v])=>addSocialRow(k,v));

                document.getElementById('pageLoader').classList.add('hidden');
                document.getElementById('mainContent').classList.remove('hidden');
                document.getElementById('mainContent').classList.add('flex');

            } catch (e) { alert('Error cargando perfil: ' + e.message); }
        }

        // --- GUARDAR ---
        document.getElementById('profileForm').addEventListener('submit', async e=>{
            e.preventDefault();
            const p1 = document.getElementById('pass1').value;
            const p2 = document.getElementById('pass2').value;
            if(p1 && p1 !== p2) {
                await Swal.fire({
                    title: 'Contraseñas no coinciden',
                    text: 'Las contraseñas no coinciden.',
                    icon: 'warning',
                    confirmButtonColor: '#4F46E5'
                });
                return;
            }

            const soc={}; document.querySelectorAll('#socialContainer > div').forEach(r=>{ const k=r.querySelector('.s-key').value; const v=r.querySelector('.s-val').value; if(v)soc[k]=v; });
            document.getElementById('socialLinksJson').value = JSON.stringify(soc);

            const fd = new FormData(e.target);
            const d=document.getElementById('dobDay').value, m=document.getElementById('dobMonth').value, y=document.getElementById('dobYear').value;
            if(d&&m) fd.set('birthdate', `${y||'1000'}-${m.toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}`); 
            else fd.set('birthdate','');

            const btn = e.submitter; btn.disabled=true; btn.innerHTML = '<i class="ph-bold ph-spinner animate-spin"></i> Guardando...';
            
            try{ 
                const r=await (await fetch(`${API_URL}?action=update_profile`,{method:'POST',body:fd})).json(); 
                await Swal.fire({
                    title: r.success ? '¡Actualizado!' : 'Error',
                    text: r.message,
                    icon: r.success ? 'success' : 'error',
                    confirmButtonColor: '#4F46E5'
                });
                if(r.success) loadProfile();
            } catch(x){ 
                await Swal.fire({
                    title: 'Error',
                    text: 'Error de conexión',
                    icon: 'error',
                    confirmButtonColor: '#4F46E5'
                });
            }
            finally { btn.disabled=false; btn.innerHTML = 'Guardar Cambios'; }
        });

        loadProfile();
    </script>
</body>
</html>