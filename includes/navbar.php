<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['user_role'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? 'Invitado';
$userId = $_SESSION['user_id'] ?? 0;

function isActive($pageName, $currentPage) {
    return $currentPage === $pageName 
        ? 'border-indigo-500 text-gray-900 bg-indigo-50' 
        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50';
}
?>

<!-- SweetAlert2 (Alertas Bonitas) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- LOADER GLOBAL (Oculto por defecto) -->
<div id="globalLoader" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-[9999] hidden flex-col items-center justify-center transition-opacity duration-300">
    <div class="relative">
        <div class="w-16 h-16 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <i class="ph-fill ph-calendar text-indigo-600 text-xl animate-pulse"></i>
        </div>
    </div>
    <span class="mt-4 text-indigo-800 font-bold text-xs tracking-[0.2em] animate-pulse">PROCESANDO...</span>
</div>

<script>
    // Objeto Global de UI para reutilizar en todas las páginas
    const UI = {
        loader: document.getElementById('globalLoader'),
        
        showLoading: function() {
            if (this.loader) {
                this.loader.classList.remove('hidden');
                this.loader.classList.add('flex');
            }
        },
        
        hideLoading: function() {
            if (this.loader) {
                this.loader.classList.add('hidden');
                this.loader.classList.remove('flex');
            }
        },
        
        // --- FUNCIONES QUE FALTABAN ---
        success: function(msg) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: msg,
                timer: 2000,
                showConfirmButton: false
            });
        },

        error: function(msg) {
            Swal.fire({
                icon: 'error',
                title: 'Ocurrió un error',
                text: msg
            });
        },
        // -----------------------------

        toast: function(title, icon = 'success') {
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                timerProgressBar: true, didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({ icon: icon, title: title });
        },

        confirm: function(title, text, confirmBtnText, callback) {
            Swal.fire({
                title: title, text: text, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#4F46E5', cancelButtonColor: '#EF4444',
                confirmButtonText: confirmBtnText, cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) callback();
            });
        }
    };
</script>

<nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <a href="index.php" class="flex-shrink-0 flex items-center group">
                    <i class="ph-duotone ph-cake text-3xl text-indigo-600 mr-2 group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-xl text-gray-800 hidden sm:block tracking-tight">EventApp</span>
                </a>
                
                <?php if ($isLoggedIn): ?>
                <div class="hidden sm:ml-8 sm:flex sm:space-x-2">
                    <a href="index.php" class="<?php echo isActive('index.php', $current_page); ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium transition-all">
                        <i class="ph ph-sun-horizon mr-2 text-lg"></i> Hoy
                    </a>
                    <a href="calendar.php" class="<?php echo isActive('calendar.php', $current_page); ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium transition-all">
                        <i class="ph ph-calendar-blank mr-2 text-lg"></i> Calendario
                    </a>
                    <a href="events.php" class="<?php echo isActive('events.php', $current_page); ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium transition-all">
                        <i class="ph ph-list-checks mr-2 text-lg"></i> Agenda
                    </a>
                    
                    <?php if (in_array($role, ['admin', 'moderator'])): ?>
                    <a href="event_types.php" class="<?php echo isActive('event_types.php', $current_page); ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium transition-all">
                        <i class="ph ph-paint-brush-broad mr-2 text-lg"></i> Tipos
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($role === 'admin'): ?>
                    <a href="users.php" class="<?php echo isActive('users.php', $current_page); ?> inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium transition-all">
                        <i class="ph ph-users-three mr-2 text-lg"></i> Usuarios
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Perfil / Login -->
            <div class="flex items-center">
                <?php if ($isLoggedIn): ?>
                    <a href="profile.php" class="flex items-center mr-3 bg-gray-50 pl-1 pr-3 py-1 rounded-full border border-gray-200 shadow-sm group hover:bg-indigo-50 transition gap-2">
                        <img src="public/get_avatar.php?id=<?php echo $userId; ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($userName); ?>&background=random'"
                             class="w-8 h-8 rounded-full object-cover border border-white shadow-sm">
                        <div class="flex flex-col text-right">
                            <span class="text-xs font-bold text-gray-700 leading-none truncate max-w-[100px]"><?php echo htmlspecialchars($userName); ?></span>
                            <span class="text-[9px] text-gray-400 uppercase font-semibold leading-none mt-0.5"><?php echo htmlspecialchars($role); ?></span>
                        </div>
                    </a>
                    <a href="public/logout.php" class="text-gray-400 hover:text-red-600 transition p-2 rounded-full hover:bg-red-50" title="Salir"><i class="ph-bold ph-sign-out text-xl"></i></a>
                <?php else: ?>
                    <div class="space-x-3 flex items-center">
                        <a href="login.php" class="text-gray-500 hover:text-indigo-600 font-bold text-sm transition">Ingresar</a>
                        <a href="register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-bold text-sm shadow-md transition transform hover:-translate-y-0.5">Crear Cuenta</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>