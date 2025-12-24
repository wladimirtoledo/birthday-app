<?php
// public/api/types.php
require_once 'common.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// =======================================================================
// 1. OBTENER TIPOS (LISTA)
// =======================================================================
if ($action === 'list' && $method === 'GET') {
    // Ordenamos por importancia visual para que en el select aparezcan ordenados
    // Fondo > Cinta > Etiqueta > Foto > Detalle > Bloque normal
    $sql = "SELECT * FROM event_types 
            ORDER BY FIELD(display_mode, 'background', 'banner', 'badge', 'photo', 'detailed') DESC, name ASC";
    
    $data = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    sendJson($data);
}

// =======================================================================
// 2. GUARDAR TIPO (Crear o Editar)
// =======================================================================
if ($action === 'save' && $method === 'POST') {
    if (!in_array($userRole, ['admin', 'moderator'])) sendError('Denegado');
    
    // Obtener JSON del body (porque event_types.php envía JSON, no FormData)
    $in = json_decode(file_get_contents('php://input'), true);

    if (!$in) sendError('Datos inválidos');

    $name = $in['name'] ?? '';
    $color = $in['color'] ?? '#000000';
    $mode = $in['display_mode'] ?? 'block';
    $icon = $in['icon'] ?? 'circle';
    $id = $in['id'] ?? null;

    if (!empty($id)) {
        // UPDATE
        $sql = "UPDATE event_types SET name=?, color=?, display_mode=?, icon=? WHERE id=?";
        $conn->prepare($sql)->execute([$name, $color, $mode, $icon, $id]);
        sendSuccess('Tipo de evento actualizado.');
    } else {
        // INSERT
        $sql = "INSERT INTO event_types (name, color, display_mode, icon) VALUES (?, ?, ?, ?)";
        $conn->prepare($sql)->execute([$name, $color, $mode, $icon]);
        sendSuccess('Tipo de evento creado.');
    }
}

// =======================================================================
// 3. ELIMINAR TIPO
// =======================================================================
if ($action === 'delete' && $method === 'POST') {
    if (!in_array($userRole, ['admin', 'moderator'])) sendError('Denegado');
    
    $in = json_decode(file_get_contents('php://input'), true);
    $id = $in['id'] ?? 0;

    // 1. Protección de Tipos de Sistema
    $chkS = $conn->prepare("SELECT slug FROM event_types WHERE id = ?");
    $chkS->execute([$id]);
    $slug = $chkS->fetchColumn();

    if (in_array($slug, ['birthday', 'holiday'])) {
        sendError('No se pueden eliminar los tipos protegidos del sistema (Cumpleaños, Feriados).');
    }

    // 2. Protección de Integridad Referencial
    $chk = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_type_id = ?");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        sendError('No se puede eliminar: Hay eventos utilizando este tipo.');
    }

    $conn->prepare("DELETE FROM event_types WHERE id = ?")->execute([$id]);
    sendSuccess('Tipo eliminado.');
}


 if ($action === 'get_event_types' && $method === 'GET') {
        try {
            // Ordenar por importancia visual
            $sql = "SELECT * FROM event_types ORDER BY FIELD(display_mode, 'background', 'banner', 'badge', 'detailed', 'photo') DESC, name ASC";
            $stmt = $conn->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            // Si falla el ordenamiento complejo, devolver lista simple
            $stmt = $conn->query("SELECT * FROM event_types ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        exit;
    }

sendError('Acción no válida en Types API');
?>