<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - EventApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#4F46E5' } } } }
    </script>
</head>
<body class="bg-slate-900 flex flex-col items-center justify-center min-h-screen font-sans py-10 relative">

    <!-- BOTÓN VOLVER AL INICIO -->
    <a href="index.php" class="absolute top-6 left-6 text-slate-400 hover:text-white transition flex items-center gap-2 text-sm font-bold group">
        <i class="ph ph-arrow-left text-lg group-hover:-translate-x-1 transition-transform"></i> Volver al Inicio
    </a>

    <div class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 transform transition-all">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Crear Cuenta</h1>
                <p class="text-gray-500 text-sm mt-1">Únete para gestionar tus eventos</p>
            </div>

            <div id="alertBox" class="hidden p-4 mb-6 rounded text-sm text-center font-bold"></div>

            <form id="registerForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nombres</label>
                        <input type="text" id="first_name" required class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Apellidos</label>
                        <input type="text" id="last_name" required class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Correo Electrónico</label>
                    <input type="email" id="email" required class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <!-- AGREGADO: required -->
                    <input type="date" id="birthdate" required class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none text-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Obligatorio para el registro.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña</label>
                    <input type="password" id="password" required minlength="6" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none text-sm">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-primary hover:bg-indigo-700 focus:outline-none transition-transform transform active:scale-95">
                        Registrarse
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-500">¿Ya tienes cuenta?</p>
                <a href="login.php" class="font-bold text-primary hover:text-indigo-500 transition">Inicia sesión</a>
            </div>
        </div>
        
        <!-- Footer simple -->
        <div class="mt-8 text-center text-slate-500 text-xs">
            &copy; <?php echo date('Y'); ?> EventApp System
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const data = {
                action: 'register',
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                birthdate: document.getElementById('birthdate').value,
                password: document.getElementById('password').value
            };

            const alertBox = document.getElementById('alertBox');
            const btn = e.target.querySelector('button');
            const originalText = btn.innerText;

            btn.innerText = 'Procesando...';
            btn.disabled = true;
            alertBox.classList.add('hidden');

            try {
                const response = await fetch('public/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                alertBox.classList.remove('hidden');
                
                if (result.success) {
                    alertBox.className = "bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded text-sm text-center font-bold block";
                    alertBox.innerText = "¡Registro enviado! Tu cuenta está en revisión. El administrador debe activarla antes de que puedas ingresar.";
                    document.getElementById('registerForm').reset();
                    btn.innerText = 'Enviado';
                } else {
                    alertBox.className = "bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm text-center font-bold block";
                    alertBox.innerText = result.message;
                    btn.innerText = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alertBox.className = "bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm text-center font-bold block";
                alertBox.innerText = 'Error de conexión.';
                alertBox.classList.remove('hidden');
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>