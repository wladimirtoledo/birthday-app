<?php
// users.php
require_once 'includes/functions.php';
requireAuth();
if (!hasRole(['admin', 'moderator'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-50 font-sans h-screen flex flex-col overflow-hidden">
    <div class="shrink-0"><?php include 'includes/navbar.php'; ?></div>
    
    <main class="flex-1 flex flex-col max-w-7xl mx-auto w-full py-6 px-4 min-h-0">
        <div class="flex justify-between items-center mb-4 shrink-0">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><i class="ph-duotone ph-users-three text-indigo-600"></i> Directorio</h1>
            <div class="flex gap-3">
                <input type="text" id="searchInput" placeholder="Buscar..." class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <button onclick="openUserModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg shadow text-sm font-medium transition flex items-center gap-2"><i class="ph-bold ph-plus"></i> Nuevo</button>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-t-xl border border-gray-200 flex-1 overflow-hidden flex flex-col">
            <div class="overflow-auto flex-1 custom-scroll">
                <table class="min-w-full divide-y divide-gray-200 relative">
                    <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm text-xs uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Usuario</th>
                            <th class="px-6 py-3 text-left">Rol</th>
                            <th class="px-6 py-3 text-left">Nacimiento</th>
                            <th class="px-6 py-3 text-left">Estado</th>
                            <th class="px-6 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody" class="bg-white divide-y divide-gray-100 text-sm"></tbody>
                </table>
            </div>
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-3 flex items-center justify-between shrink-0">
                <span class="text-xs text-gray-500 font-medium" id="pageInfo">...</span>
                <div class="flex space-x-2"><button id="prevBtn" onclick="changePage(-1)" class="px-3 py-1 border rounded text-xs font-bold bg-white">Anterior</button><button id="nextBtn" onclick="changePage(1)" class="px-3 py-1 border rounded text-xs font-bold bg-white">Siguiente</button></div>
            </div>
        </div>
    </main>
    <div class="shrink-0 mt-auto"><?php include 'includes/footer.php'; ?></div>

    <!-- MODAL GRANDE COMPLETO -->
    <div id="userModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[90vh] flex overflow-hidden">
            
            <div class="w-full flex flex-col h-full">
                <!-- Header -->
                <div class="px-8 py-5 border-b flex justify-between items-center bg-gray-50 shrink-0">
                    <h2 class="text-xl font-bold text-gray-800" id="modalTitle">Usuario</h2>
                    <button onclick="closeUserModal()" class="text-gray-400 hover:text-gray-600"><i class="ph-bold ph-x text-xl"></i></button>
                </div>

                <!-- Body Scrollable (Formulario idéntico a profile.php) -->
                <div class="flex-1 overflow-y-auto custom-scroll p-8 bg-white">
                    <form id="userForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        <input type="hidden" name="id" id="userId">
                        
                        <!-- Columna Izquierda (Avatar, Bio, Redes) -->
                        <div class="lg:col-span-4 space-y-6">
                            <div class="flex flex-col items-center">
                                <div class="relative group cursor-pointer mb-3 w-32 h-32">
                                    <img id="avatarPreview" src="https://via.placeholder.com/150" class="w-full h-full rounded-full object-cover border-4 border-indigo-50 shadow-md">
                                    <button type="button" onclick="document.getElementById('userAvatar').click()" class="absolute bottom-0 right-0 bg-indigo-600 text-white p-2 rounded-full shadow-lg hover:bg-indigo-700 transition border-2 border-white"><i class="ph-bold ph-camera"></i></button>
                                    <input type="file" name="avatar" id="userAvatar" accept="image/*" class="hidden">
                                </div>
                                <input type="url" name="avatar_url_input" id="avatarUrl" placeholder="O pega URL..." class="text-xs border-b border-gray-300 text-center w-full focus:border-indigo-500 outline-none pb-1">
                            </div>

                            <div class="bg-white border rounded-xl p-4 shadow-sm">
                                <h3 class="font-bold text-gray-800 text-xs uppercase mb-2">Bio</h3>
                                <textarea name="bio" id="bio" rows="3" class="w-full border rounded-lg p-2 text-sm resize-none"></textarea>
                            </div>

                            <div class="bg-white border rounded-xl p-4 shadow-sm">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-bold text-gray-800 text-xs uppercase">Redes</h3>
                                    <button type="button" onclick="addSocialRow()" class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded font-bold hover:bg-indigo-100">+</button>
                                </div>
                                <div id="socialContainer" class="space-y-2"></div>
                                <input type="hidden" name="social_links_json" id="socialLinksJson">
                            </div>
                        </div>

                        <!-- Columna Derecha (Datos) -->
                        <div class="lg:col-span-8 space-y-5">
                            <h3 class="font-bold text-gray-900 border-b pb-2">Información Personal</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Nickname (Único)</label><input type="text" name="nickname" id="nickname" required class="w-full border p-2 rounded-lg text-sm bg-gray-50 font-bold text-gray-700 uppercase" oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,'')"></div>
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Email</label><input type="email" name="email" id="email" required class="w-full border p-2 rounded-lg text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Nombres</label><input type="text" name="first_name" id="firstName" required class="w-full border p-2 rounded-lg text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Apellidos</label><input type="text" name="last_name" id="lastName" required class="w-full border p-2 rounded-lg text-sm"></div>
                                
                                <div class="col-span-2 md:col-span-1 bg-indigo-50/50 p-2 rounded border border-indigo-100">
                                    <label class="text-xs font-bold text-indigo-700 block mb-1">Nacimiento</label>
                                    <div class="flex gap-1">
                                        <select id="dobDay" class="border p-1.5 rounded text-xs w-14"><option value="">D</option><?php for($i=1;$i<=31;$i++)echo"<option value='$i'>$i</option>";?></select>
                                        <select id="dobMonth" class="border p-1.5 rounded text-xs flex-1"><option value="">Mes</option><option value="1">Ene</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Abr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Ago</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dic</option></select>
                                        <input type="number" id="dobYear" placeholder="Año" class="border p-1.5 rounded text-xs w-16">
                                    </div>
                                    <input type="hidden" name="birthdate" id="finalBirthdate">
                                </div>
                                
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Teléfono</label><input type="text" name="phone" id="phone" class="w-full border p-2 rounded-lg text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Género</label><select name="gender" id="gender" class="w-full border p-2 rounded-lg text-sm bg-white"><option value="">-</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option><option value="Otro">Otro</option></select></div>
                            </div>

                            <h3 class="font-bold text-gray-900 border-b pb-2 pt-2">Sistema y Ubicación</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Rol</label><select name="role" id="userRole" class="w-full border p-2 rounded-lg text-sm bg-white"><option value="user">Usuario</option><option value="moderator">Moderador</option><option value="admin">Administrador</option></select></div>
                                <div><label class="text-xs font-bold text-gray-600 block mb-1">Estado</label><select name="status" id="userStatus" class="w-full border p-2 rounded-lg text-sm bg-white"><option value="active">Activo</option><option value="banned_login">Bloqueado</option><option value="banned_create">Lectura</option><option value="banned_view">Ciego</option></select></div>
                                
                                <div class="col-span-2 grid grid-cols-3 gap-2">
                                    <select name="country" id="country" class="border p-2 rounded text-xs w-full"><option value="">País...</option></select>
                                    <select name="region" id="region" class="border p-2 rounded text-xs w-full" disabled><option value="">Provincia</option></select>
                                    <select name="city" id="city" class="border p-2 rounded text-xs w-full" disabled><option value="">Ciudad</option></select>
                                </div>
                            </div>

                            <div class="bg-red-50 p-4 rounded-xl border border-red-100 mt-2">
                                <h3 class="text-xs font-bold text-red-700 uppercase mb-2">Cambiar Contraseña</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="password" name="password" id="userPass" placeholder="Nueva..." class="border p-2 rounded-lg text-sm">
                                    <input type="password" id="confirmPass" placeholder="Confirmar..." class="border p-2 rounded-lg text-sm">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Footer -->
                <div class="px-8 py-5 border-t bg-gray-50 flex justify-end gap-3 shrink-0">
                    <button onclick="closeUserModal()" class="px-5 py-2.5 bg-white border border-gray-300 rounded-xl text-gray-700 font-bold hover:bg-gray-100 text-sm">Cancelar</button>
                    <button type="submit" form="userForm" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-md text-sm">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'public/api/users.php'; // USAR API MODULAR
        let currentPage = 1, limit = 10, totalPages = 1;

        // --- FUNCIONES COMUNES ---
        function formatDateLocal(d) { if(!d)return'-'; const [y,m,day]=d.split('-'); const mo=["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Sep","Oct","Nov","Dic"]; return `${day} ${mo[parseInt(m)-1]} ${y==='1000'?'':y}`; }
        function addSocialRow(k='',v=''){
            const div = document.createElement('div'); div.className="flex gap-1 items-center";
            div.innerHTML = `<select class="s-key border p-1 rounded text-xs w-20"><option value="steam">Steam</option><option value="discord">Discord</option><option value="facebook">FB</option><option value="instagram">IG</option><option value="other">Otro</option></select><input type="text" class="s-val border p-1 rounded text-xs w-full" value="${v}"><button type="button" onclick="this.parentElement.remove()" class="text-red-500 px-1">x</button>`;
            document.getElementById('socialContainer').appendChild(div); if(k)div.querySelector('.s-key').value=k;
        }

        // --- GEO ---
        async function loadGeo(type, pId, tId) {
            const sel=document.getElementById(tId); sel.innerHTML='<option>Cargando...</option>'; sel.disabled=true;
            try{ const r=await fetch(`${API_URL}?action=get_geo_data&type=${type}&parent_id=${pId||''}`); const d=await r.json();
                 sel.innerHTML='<option value="">Seleccione...</option>'; d.forEach(i=>{const o=document.createElement('option');o.value=i.id;o.text=i.name;sel.appendChild(o);}); sel.disabled=false;
            } catch(e){sel.innerHTML='<option>Error</option>';}
        }
        document.getElementById('country').addEventListener('change', function(){ loadGeo('regions',this.value,'region'); document.getElementById('city').innerHTML='<option>...</option>'; document.getElementById('city').disabled=true; });
        document.getElementById('region').addEventListener('change', function(){ loadGeo('cities',this.value,'city'); });

        // --- IMAGEN ---
        document.getElementById('userAvatar').addEventListener('change', e=>{if(e.target.files[0]){const r=new FileReader();r.onload=ev=>document.getElementById('avatarPreview').src=ev.target.result;r.readAsDataURL(e.target.files[0]);}});
        document.getElementById('avatarUrl').addEventListener('input', e=>{if(e.target.value.length>5)document.getElementById('avatarPreview').src=e.target.value;});

        // --- LISTADO ---
        let timeout; document.getElementById('searchInput').addEventListener('input', e=>{clearTimeout(timeout); timeout=setTimeout(()=>{currentPage=1;loadUsers(e.target.value);},300);});
        
        async function loadUsers(search='') {
            const tbody = document.getElementById('usersTableBody');
            try {
                const res = await fetch(`${API_URL}?action=get_users&page=${currentPage}&limit=${limit}&search=${search}`);
                const r = await res.json();
                const users = r.data || [];
                totalPages = Math.ceil(r.total / limit);
                document.getElementById('pageInfo').innerText = `Pág ${currentPage} de ${totalPages||1}`;
                document.getElementById('prevBtn').disabled = currentPage===1;
                document.getElementById('nextBtn').disabled = currentPage>=totalPages;

                tbody.innerHTML = users.map(u => {
                    let stCls = u.status==='active'?'text-green-600':'text-red-500';
                    let av = u.avatar_url || `https://ui-avatars.com/api/?name=${u.first_name}+${u.last_name}`;
                    return `<tr class="hover:bg-gray-50 border-b"><td class="px-6 py-3 flex items-center gap-3"><img src="${av}" class="h-8 w-8 rounded-full border"><div><div class="font-bold">${u.first_name} ${u.last_name}</div><div class="text-xs text-gray-400">@${u.nickname}</div></div></td><td class="px-6 py-3 text-xs uppercase font-bold text-gray-600">${u.role}</td><td class="px-6 py-3 text-sm">${formatDateLocal(u.birthdate)}</td><td class="px-6 py-3 text-xs font-bold uppercase ${stCls}">${u.status}</td><td class="px-6 py-3 text-right"><button onclick="editUser(${u.id})" class="text-indigo-600 font-bold text-xs bg-indigo-50 px-3 py-1 rounded hover:bg-indigo-100">Editar</button></td></tr>`;
                }).join('');
            } catch(e){}
        }
        function changePage(d) { currentPage+=d; loadUsers(document.getElementById('searchInput').value); }

        // --- EDICIÓN COMPLETA (FETCH DETAIL) ---
        async function editUser(id) {
            // 1. Cargar Países
            await loadGeo('countries', null, 'country');
            
            // 2. Traer datos completos del usuario
            try {
                const res = await fetch(`${API_URL}?action=get_user_detail&id=${id}`);
                const u = await res.json();
                if(!u.id) return alert('Error cargando usuario');

                // Llenar form
                document.getElementById('modalTitle').innerText='Editar Usuario';
                document.getElementById('userId').value=u.id;
                document.getElementById('nickname').value=u.nickname;
                document.getElementById('firstName').value=u.first_name;
                document.getElementById('lastName').value=u.last_name;
                document.getElementById('email').value=u.email;
                document.getElementById('bio').value=u.bio||'';
                document.getElementById('phone').value=u.phone||'';
                document.getElementById('gender').value=u.gender||'';

                // Fecha
                if(u.birthdate){ const [y,m,d]=u.birthdate.split('-'); document.getElementById('dobDay').value=parseInt(d); document.getElementById('dobMonth').value=parseInt(m); document.getElementById('dobYear').value=(y==='1000')?'':y; }
                else { document.getElementById('dobDay').value=''; document.getElementById('dobMonth').value=''; document.getElementById('dobYear').value=''; }

                // Geo Cascada
                if(u.country) {
                    document.getElementById('country').value=u.country;
                    await loadGeo('regions', u.country, 'region');
                    if(u.region) {
                        document.getElementById('region').value=u.region;
                        await loadGeo('cities', u.region, 'city');
                        if(u.city) document.getElementById('city').value=u.city;
                    }
                }

                // Redes
                document.getElementById('socialContainer').innerHTML='';
                if(u.social_links) Object.entries(u.social_links).forEach(([k,v])=>addSocialRow(k,v));

                document.getElementById('avatarPreview').src = u.avatar_url || `https://ui-avatars.com/api/?name=${u.first_name}`;
                document.getElementById('userRole').value=u.role;
                document.getElementById('userStatus').value=u.status;
                document.getElementById('userPass').value=''; document.getElementById('confirmPass').value='';

                document.getElementById('userModal').classList.remove('hidden');

            } catch(e) { alert('Error de conexión'); }
        }

        function openUserModal(){ document.getElementById('userForm').reset(); document.getElementById('userId').value=''; document.getElementById('avatarPreview').src='https://ui-avatars.com/api/?name=U'; document.getElementById('modalTitle').innerText='Nuevo'; document.getElementById('socialContainer').innerHTML=''; loadGeo('countries',null,'country'); document.getElementById('userModal').classList.remove('hidden'); }
        function closeUserModal(){ document.getElementById('userModal').classList.add('hidden'); }

        document.getElementById('userForm').addEventListener('submit', async e=>{
            e.preventDefault();
            if(document.getElementById('userPass').value !== document.getElementById('confirmPass').value) return alert('Passwords no coinciden');

            const soc={}; document.querySelectorAll('#socialContainer > div').forEach(r=>{ const k=r.querySelector('.s-key').value; const v=r.querySelector('.s-val').value; if(v)soc[k]=v; });
            document.getElementById('socialLinksJson').value = JSON.stringify(soc);

            const fd = new FormData(e.target);
            const d=document.getElementById('dobDay').value, m=document.getElementById('dobMonth').value, y=document.getElementById('dobYear').value||'1000';
            if(d&&m) fd.set('birthdate', `${y}-${m.toString().padStart(2,'0')}-${d.toString().padStart(2,'0')}`); else fd.set('birthdate','');

            try{ const r=await (await fetch(`${API_URL}?action=save_user`,{method:'POST',body:fd})).json(); alert(r.message); if(r.success){closeUserModal();loadUsers();} } catch(x){alert('Error');}
        });

        loadUsers();
    </script>
</body>
</html>