<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - EventApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#4F46E5' } } } }
    </script>
</head>
<body class="bg-slate-900 flex flex-col items-center justify-center h-screen font-sans relative">

    <!-- BOTÓN VOLVER AL INICIO -->
    <a href="index.php" class="absolute top-6 left-6 text-slate-400 hover:text-white transition flex items-center gap-2 text-sm font-bold group">
        <i class="ph ph-arrow-left text-lg group-hover:-translate-x-1 transition-transform"></i> Volver al Inicio
    </a>

    <div class="w-full max-w-md p-8">
        <div class="bg-white rounded-2xl shadow-2xl p-8 transform transition-all">
            <div class="text-center mb-8">
                <div class="bg-indigo-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 text-primary">
                    <i class="ph ph-cake text-3xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Bienvenido</h1>
                <p class="text-gray-500 text-sm mt-1">Inicia sesión para gestionar tu agenda</p>
            </div>

            <!-- Alerta -->
            <div id="alertBox" class="hidden bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm" role="alert">
                <p id="alertMessage"></p>
            </div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="ph ph-envelope absolute left-3 top-3 text-gray-400"></i>
                        <input type="email" id="email" name="email" required 
                            class="pl-10 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                    <div class="relative">
                        <i class="ph ph-lock-key absolute left-3 top-3 text-gray-400"></i>
                        <input type="password" id="password" name="password" required 
                            class="pl-10 block w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary outline-none transition sm:text-sm">
                    </div>
                </div>

                <button type="submit" 
                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-primary hover:bg-indigo-700 focus:outline-none transition-transform transform active:scale-95">
                    Iniciar Sesión
                </button>
            </form>

            <div class="mt-8 text-center border-t border-gray-100 pt-6">
                <p class="text-sm text-gray-500">¿No tienes una cuenta?</p>
                <a href="register.php" class="font-bold text-primary hover:text-indigo-500 transition">Regístrate aquí</a>
            </div>
        </div>
        
        <!-- Footer simple -->
        <div class="mt-8 text-center text-slate-500 text-xs">
            &copy; <?php echo date('Y'); ?> EventApp System
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const alertBox = document.getElementById('alertBox');
            const alertMsg = document.getElementById('alertMessage');
            const btn = e.target.querySelector('button');
            const originalBtnText = btn.innerText;

            btn.innerText = 'Verificando...';
            btn.disabled = true;
            alertBox.classList.add('hidden');

            try {
                const response = await fetch('public/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', email, password })
                });
                
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.data.redirect;
                } else {
                    alertBox.classList.remove('hidden');
                    alertMsg.innerText = result.message;
                    btn.innerText = originalBtnText;
                    btn.disabled = false;
                }
            } catch (error) {
                console.error(error);
                alertBox.classList.remove('hidden');
                alertMsg.innerText = 'Error de conexión con el servidor.';
                btn.innerText = originalBtnText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>