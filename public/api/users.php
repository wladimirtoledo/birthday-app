<?php
// public/api/users.php
require_once 'common.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// =======================================================================
// 1. OBTENER DATOS GEOGRÁFICOS (País > Región > Ciudad)
// =======================================================================
if ($action === 'get_geo_data' && $method === 'GET') {
    $type = $_GET['type'] ?? 'countries';
    $parentId = $_GET['parent_id'] ?? null;
    
    $data = [];
    if ($type === 'countries') {
        $data = $conn->query("SELECT id, name FROM geo_countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'regions' && $parentId) {
        $stmt = $conn->prepare("SELECT id, name FROM geo_regions WHERE country_id = ? ORDER BY name ASC");
        $stmt->execute([$parentId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'cities' && $parentId) {
        $stmt = $conn->prepare("SELECT id, name FROM geo_cities WHERE region_id = ? ORDER BY name ASC");
        $stmt->execute([$parentId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    sendJson($data);
}

// =======================================================================
// 2. OBTENER PERFIL (Usuario actual o ID específico por Admin)
// =======================================================================
if (($action === 'get_profile' || $action === 'get_user_detail') && $method === 'GET') {
    if ($userId === 0) sendError('Acceso denegado');
    
    // Si es admin pidiendo detalle, usa $_GET['id'], si no, usa la sesión
    $targetId = ($action === 'get_user_detail' && in_array($userRole, ['admin', 'moderator'])) 
                ? ($_GET['id'] ?? $userId) 
                : $userId;

    $sql = "SELECT id, name as nickname, first_name, last_name, email, role, status, birthdate, avatar, 
                   bio, phone, gender, country, region, city, social_links 
            FROM users WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$targetId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Procesar Avatar a Base64
        $user['avatar_url'] = !empty($user['avatar']) ? 'data:image/jpeg;base64,'.base64_encode($user['avatar']) : null;
        unset($user['avatar']); // Limpiar binario
        
        // Decodificar JSON de redes sociales
        $user['social_links'] = json_decode($user['social_links'] ?? '{}', true);
        
        sendJson($user);
    } else {
        sendError('Usuario no encontrado');
    }
}

// =======================================================================
// 3. GUARDAR USUARIO (Registro, Perfil Propio o Admin Editando)
// =======================================================================
if (($action === 'save_user' || $action === 'update_profile') && $method === 'POST') {
    if ($userId === 0) sendError('Acceso denegado');

    // Determinar ID objetivo
    $targetId = ($action === 'save_user' && in_array($userRole, ['admin', 'moderator'])) 
                ? ($_POST['id'] ?? null) 
                : $userId;

    // Recibir datos básicos
    $nick = trim($_POST['nickname'] ?? '');
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($first) || empty($last) || empty($email)) sendError('Nombre, Apellido y Email son obligatorios.');

    // Validar formato Nickname (Solo letras y números)
    if (!preg_match('/^[a-zA-Z0-9]+$/', $nick)) {
        sendError('El Nickname solo puede contener letras y números.');
    }

    // Validar Unicidad (Nickname y Email)
    // Buscamos si existe otro usuario con el mismo email o nick que NO sea el usuario actual
    $sqlCheck = "SELECT id FROM users WHERE (email = ? OR name = ?) AND id != ?";
    $chk = $conn->prepare($sqlCheck);
    $chk->execute([$email, $nick, $targetId ? $targetId : 0]);
    if ($chk->rowCount() > 0) sendError('El email o el nickname ya están en uso.');

    // Recibir resto de datos
    $bio = $_POST['bio'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $country = $_POST['country'] ?? null; 
    $region = $_POST['region'] ?? null; 
    $city = $_POST['city'] ?? null;
    $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
    $socialJson = $_POST['social_links_json'] ?? '{}';
    
    // Roles y Estado
    if ($action === 'save_user' && in_array($userRole, ['admin', 'moderator'])) {
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
    } else {
        // Mantener rol actual si se edita a sí mismo
        if ($targetId) {
            $curr = $conn->query("SELECT role, status FROM users WHERE id=$targetId")->fetch();
            $role = $curr['role'];
            $status = $curr['status'];
        } else {
            // Nuevo registro por defecto (si llegara a pasar por aquí)
            $role = 'user';
            $status = 'banned_login'; 
        }
    }

    // Procesar Avatar
    $avBlob = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $avBlob = file_get_contents($_FILES['avatar']['tmp_name']);
    } elseif (!empty($_POST['avatar_url_input'])) {
        $url = $_POST['avatar_url_input'];
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $c = @file_get_contents($url);
            if ($c) $avBlob = $c;
        }
    }

    if ($targetId) {
        // UPDATE
        $sql = "UPDATE users SET 
                name=:ni, first_name=:f, last_name=:l, email=:e, bio=:b, phone=:ph, gender=:ge, 
                country=:co, region=:re, city=:ci, birthdate=:bd, social_links=:so, role=:ro, status=:st";
        
        $params = [
            ':ni'=>$nick, ':f'=>$first, ':l'=>$last, ':e'=>$email, ':b'=>$bio, ':ph'=>$phone, ':ge'=>$gender,
            ':co'=>$country, ':re'=>$region, ':ci'=>$city, ':bd'=>$birthdate, ':so'=>$socialJson, 
            ':ro'=>$role, ':st'=>$status, ':id'=>$targetId
        ];
        
        if ($avBlob) { $sql .= ", avatar=:av"; $params[':av'] = $avBlob; }
        if (!empty($_POST['password'])) { 
            $sql .= ", password=:pass"; 
            $params[':pass'] = password_hash($_POST['password'], PASSWORD_DEFAULT); 
        }
        
        $sql .= " WHERE id=:id";
        $conn->prepare($sql)->execute($params);
        
        // Actualizar sesión si es el propio usuario
        if($targetId == $userId) $_SESSION['user_name'] = $first . ' ' . $last;
        
        sendSuccess('Datos actualizados correctamente.');
    } else {
        // INSERT (Nuevo usuario desde panel admin)
        $pass = $_POST['password'] ?? '';
        if(empty($pass)) sendError('Contraseña requerida para nuevos usuarios.');

        $sql = "INSERT INTO users (name, first_name, last_name, email, password, role, status, bio, phone, gender, country, region, city, birthdate, social_links, avatar) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        
        $conn->prepare($sql)->execute([
            $nick, $first, $last, $email, password_hash($pass, PASSWORD_DEFAULT),
            $role, $status, $bio, $phone, $gender, $country, $region, $city, 
            $birthdate, $socialJson, $avBlob
        ]);
        sendSuccess('Usuario creado exitosamente.');
    }
}

// =======================================================================
// 4. LISTAR USUARIOS (ADMIN - PAGINADO)
// =======================================================================
if ($action === 'get_users' && $method === 'GET') {
    if (!hasRole(['admin', 'moderator'])) sendError('Denegado');

    $s = "%".($_GET['search']??'')."%";
    // Paginación calculada en common.php o aquí
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 100;
    $offset = ($page - 1) * $limit;

    // Query Data
    $sql = "SELECT id, first_name, last_name, name as nickname, email, role, status, birthdate, avatar 
            FROM users 
            WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR name LIKE ?
            ORDER BY id DESC LIMIT $limit OFFSET $offset";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$s, $s, $s, $s]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query Total
    $cStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR name LIKE ?");
    $cStmt->execute([$s, $s, $s, $s]);
    $total = $cStmt->fetchColumn();

    // Mapeo
    $mapped = array_map(function($u) {
        $u['avatar_url'] = !empty($u['avatar']) ? 'data:image/jpeg;base64,'.base64_encode($u['avatar']) : null;
        unset($u['avatar']);
        return $u;
    }, $users);

    sendJson(['data' => $mapped, 'total' => $total]);
}

// =======================================================================
// 5. LISTA SIMPLE (PARA DROPDOWNS)
// =======================================================================
if ($action === 'get_users_simple' && $method === 'GET') {
    if (!hasRole(['admin', 'moderator'])) sendError('Denegado');
    $data = $conn->query("SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    sendJson($data);
}

sendError('Acción no válida en Users API');
?>