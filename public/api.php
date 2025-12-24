<?php
// public/api.php (EL ENRUTADOR)

$action = $_GET['action'] ?? '';

// Mapeo de Acciones -> Archivos
$routes = [
    // Eventos
    'get_events'        => 'api/events.php',
    'save_event'        => 'api/events.php',
    'delete_event'      => 'api/events.php',
    'moderate_event'    => 'api/events.php',
    
    // Usuarios y Perfil
    'get_users'         => 'api/users.php',
    'save_user'         => 'api/users.php',
    'get_users_simple'  => 'api/users.php',
    'get_profile'       => 'api/users.php',
    'update_profile'    => 'api/users.php',
    'get_geo_data'      => 'api/users.php',
    
    // Tipos de Evento
    'get_event_types'   => 'api/types.php',
    'save_event_type'   => 'api/types.php',
    'delete_event_type' => 'api/types.php',
];

if (array_key_exists($action, $routes)) {
    require_once $routes[$action];
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida por el API Router: ' . $action]);
    exit;
}
?>