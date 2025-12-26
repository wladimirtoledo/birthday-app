<?php
// public/api/events.php
require_once 'common.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// --- VERIFICACIÓN DE ESTADO Y ROL DEL USUARIO ---
$userStatus = 'active';
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT status, role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    if ($u) { $userStatus = $u['status']; $userRole = $u['role']; }
}

// 1. LISTAR EVENTOS (GET_ALL)
if ($action === 'get_all' && $method === 'GET') {
    // REGLA 12: Bloqueados o sin calendario no ven nada
    if ($userStatus === 'banned_login' || $userStatus === 'banned_view' || $userRole === 'guest') {
        sendJson(['data' => [], 'total' => 0]);
    }

    $context = $_GET['context'] ?? 'calendar';
    $rawStart = $_GET['start_date'] ?? date('Y-m-01');
    $rawEnd = $_GET['end_date'] ?? date('Y-m-t');
    
    // Margen de seguridad para asegurar que las semanas completas se carguen
    $startDate = date('Y-m-d', strtotime($rawStart . ' -30 days'));
    $endDate = date('Y-m-d', strtotime($rawEnd . ' +30 days'));

    // Cache de Tipos
    $typesRaw = $conn->query("SELECT * FROM event_types")->fetchAll(PDO::FETCH_ASSOC);
    $typesConfig = [];
    foreach($typesRaw as $t) { $typesConfig[$t['id']] = $t; if(!empty($t['slug'])) $typesConfig['slug_'.$t['slug']] = $t; }

    // --- CONSTRUCCIÓN DEL WHERE SEGÚN ROL (REGLAS 13, 14, 15, 16) ---
    $where = ["e.type != 'birthday'"]; // Los cumpleaños se cargan aparte
    $params = [];

    // REGLA 16: ADMIN ve TODO (Público y Privado)
    if ($userRole === 'admin') {
        $where[] = "1=1";
    }
    // REGLA 15: MODERADOR ve TODO (Público y Privado de todos) + Puede editar todo
    elseif ($userRole === 'moderator') {
         $where[] = "1=1";
    }
    // REGLA 14: ACTIVO ve Públicos Aprobados + Suyos (Privados/Pendientes)
    elseif ($userStatus === 'active') {
        $where[] = "((e.visibility = 'public' AND e.status = 'approved') OR (e.created_by = :uid))";
        $params[':uid'] = $userId;
    }
    // REGLA 13: SOLO LECTURA (banned_create) ve Feriados Públicos + Suyos (pero no edita)
    elseif ($userStatus === 'banned_create') {
        // Asumiendo que feriados son públicos. Ve públicos aprobados + suyos.
        $where[] = "((e.visibility = 'public' AND e.status = 'approved') OR (e.created_by = :uid))";
        $params[':uid'] = $userId;
    }

    // Filtro Fecha
    if ($startDate && $endDate) {
        $where[] = "e.event_date >= :start AND e.event_date <= :end";
        $params[':start'] = $startDate;
        $params[':end'] = $endDate;
    }

    $whereSql = implode(' AND ', $where);
    
    // Paginación (Solo para contexto management)
    $limitSql = "";
    if ($context === 'management') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 100;
        $offset = ($page - 1) * $limit;
        $limitSql = " LIMIT $limit OFFSET $offset";
    }

    $sql = "SELECT e.*, CONCAT(u.first_name,' ',u.last_name) as creator_name, et.name as type_name, et.color as type_color, et.display_mode, et.icon, et.slug as type_slug 
            FROM events e 
            LEFT JOIN users u ON e.created_by = u.id 
            LEFT JOIN event_types et ON e.event_type_id = et.id
            WHERE $whereSql ORDER BY e.event_date ASC $limitSql";

    $stmt = $conn->prepare($sql);
    foreach($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->execute();
    $dbEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total count (para paginación)
    $total = 0;
    if ($context === 'management') {
        $cStmt = $conn->prepare("SELECT COUNT(*) FROM events e WHERE $whereSql");
        foreach($params as $k=>$v) $cStmt->bindValue($k,$v);
        $cStmt->execute();
        $total = $cStmt->fetchColumn();
    }

    $finalEvents = [];
    foreach ($dbEvents as $ev) {
        if ($ev['status'] === 'pending') $ev['title'] = "⏳ " . $ev['title'];
        $ev['color'] = $ev['type_color'] ?? $ev['color'] ?? '#6B7280';
        $ev['image_url'] = !empty($ev['image']) ? 'data:image/jpeg;base64,'.base64_encode($ev['image']) : null;
        unset($ev['image']);

        // Fechas ISO con Hora
        $startISO = $ev['event_date'];
        if (!empty($ev['start_time']) && $ev['start_time']!=='00:00:00') $startISO .= 'T'.$ev['start_time'];
        $ev['start'] = $startISO;
        
        if (!empty($ev['end_date']) && $ev['end_date']!='0000-00-00') {
             $endISO = $ev['end_date'];
             if (!empty($ev['end_time']) && $ev['end_time']!=='00:00:00') $endISO .= 'T'.$ev['end_time'];
             else if ($ev['end_date'] != $ev['event_date']) $endISO = date('Y-m-d', strtotime($ev['end_date'].' +1 day')); // Inclusive
             $ev['end'] = $endISO;
        }

        // Datos Crudos para Edición
        $ev['raw_end_date'] = !empty($ev['end_date']) ? $ev['end_date'] : $ev['event_date'];
        $ev['raw_start_time'] = !empty($ev['start_time']) ? $ev['start_time'] : '';
        $ev['raw_end_time'] = !empty($ev['end_time']) ? $ev['end_time'] : '';

        // PERMISOS DE EDICIÓN
        $ev['editable'] = false;
        if ($userStatus !== 'banned_create') { // Si no es solo lectura
            if ($userRole === 'admin' || $userRole === 'moderator') {
                $ev['editable'] = true; // Admin y Mod editan todo
            } else {
                if ($ev['created_by'] == $userId) $ev['editable'] = true; // Usuario edita lo suyo
            }
        }

        if ($ev['type_slug'] === 'holiday') {
            $ev['display_mode'] = 'background';
        } else if (empty($ev['display_mode'])) {
            $ev['display_mode'] = 'block';
        }
        if (empty($ev['icon'])) $ev['icon'] = 'circle';
        $ev['order'] = ($ev['display_mode'] === 'banner') ? 1 : (($ev['display_mode'] === 'badge') ? 2 : 10);

        // Filtrar duplicados de feriados por fecha
        if ($ev['type_slug'] === 'holiday') {
            $already = array_filter($finalEvents, function($e) use ($ev) {
                return $e['type_slug'] === 'holiday' && $e['event_date'] === $ev['event_date'];
            });
            if (count($already) === 0) $finalEvents[] = $ev;
        } else {
            $finalEvents[] = $ev;
        }
    }

    // --- CUMPLEAÑOS ---
    if ($userId > 0) {
        $bConf = $typesConfig['slug_birthday'] ?? ['color'=>'#EC4899','display_mode'=>'detailed','icon'=>'cake','name'=>'Cumpleaños'];
        $userSql = "SELECT id, first_name, last_name, birthdate, avatar FROM users WHERE birthdate IS NOT NULL AND birthdate != '0000-00-00' AND status != 'banned_login'";
        $users = $conn->query($userSql)->fetchAll(PDO::FETCH_ASSOC);
        $sY=(int)date('Y',strtotime($startDate)); $eY=(int)date('Y',strtotime($endDate));

        foreach ($users as $u) {
            $dob = new DateTime($u['birthdate']);
            foreach(range($sY,$eY) as $year) {
                $bDate = "$year-".$dob->format('m-d');
                // Bisiesto fix
                if($dob->format('m-d')=='02-29' && !checkdate(2,29,$year)) $bDate="$year-02-28";
                
                if ($startDate && ($bDate < $startDate || $bDate > $endDate)) continue;

                $age = ($dob->format('Y') == 1000) ? null : ($year - $dob->format('Y'));
                $av = !empty($u['avatar']) ? 'data:image/jpeg;base64,'.base64_encode($u['avatar']) : null;
                
                $finalEvents[] = [
                    'id' => 'usr_bday_'.$u['id'].'_'.$year, 'title' => trim($u['first_name'].' '.$u['last_name']),
                    'start' => $bDate, 'allDay' => true, 'type' => 'birthday', 'type_name' => $bConf['name'],
                    'color' => $bConf['color'], 'display_mode' => $bConf['display_mode'], 'icon' => $bConf['icon'],
                    'visibility' => 'public', 'status' => 'approved', 'creator_name' => 'Sistema',
                    'image_url' => $av, 'editable' => false, 'age' => $age, 'order' => 5
                ];
            }
        }
    }
    
    usort($finalEvents, function($a, $b) { return strcmp($a['start'], $b['start']); });
    
    sendJson($context==='calendar' ? $finalEvents : ['data'=>$finalEvents, 'total'=>$total]);
}

// 2. SAVE EVENT
if ($action === 'save' && $method === 'POST') {
    if ($userId === 0) sendError('Login requerido');
    if ($userStatus === 'banned_create') sendError('Cuenta de solo lectura');
    
    $id = $_POST['id'] ?? null;
    $title=$_POST['title']; $date=$_POST['date']; 
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : $date;
    $startTime = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $endTime = !empty($_POST['end_time']) ? $_POST['end_time'] : null;

    // Validación Fechas
    if ($date > $endDate || ($date == $endDate && $startTime && $endTime && $startTime > $endTime)) {
        sendError('La fecha/hora de término no puede ser anterior al inicio.');
    }

    $typeId=$_POST['event_type_id']??null; $color=$_POST['color']; $vis=$_POST['visibility']; $desc=$_POST['description'];
    
    // Check tipo
    if($typeId) {
        $chk=$conn->prepare("SELECT slug FROM event_types WHERE id=?"); $chk->execute([$typeId]);
        if($chk->fetchColumn()==='birthday') sendError('No permitido crear cumpleaños manuales.');
    }

    $ownerId = $userId;
    if (in_array($userRole, ['admin', 'moderator']) && !empty($_POST['created_by'])) $ownerId = $_POST['created_by'];
    
    // Estado por defecto
    $st = 'pending';
    // Si es privado se aprueba solo. Si es público y lo crea un admin/mod se aprueba solo.
    if ($vis === 'private' || in_array($userRole, ['admin', 'moderator'])) $st = 'approved';

    // Procesar imagen del evento (validación de tamaño y tipo)
    $img = null;
    $maxImageBytes = 5 * 1024 * 1024; // 5MB
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        if ($_FILES['image']['size'] > $maxImageBytes) sendError('Imagen demasiado grande (máx 5MB).');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
        // Eliminado: $finfo->close() no existe en FileInfo
        if (!in_array($mime, $allowedImageTypes)) sendError('Tipo de imagen no permitido.');
        $img = file_get_contents($_FILES['image']['tmp_name']);
    } elseif (!empty($_POST['image_url_input'])) {
        $url = $_POST['image_url_input'];
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parts = parse_url($url);
            if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'])) sendError('URL de imagen inválida.');

            $headers = @get_headers($url, 1);
            if ($headers === false) sendError('No se pudo acceder a la URL de la imagen.');

            $contentType = '';
            if (isset($headers['Content-Type'])) $contentType = is_array($headers['Content-Type']) ? end($headers['Content-Type']) : $headers['Content-Type'];
            $contentLength = null;
            if (isset($headers['Content-Length'])) $contentLength = is_array($headers['Content-Length']) ? end($headers['Content-Length']) : $headers['Content-Length'];

            if ($contentType) {
                $ct = explode(';', $contentType)[0];
                if (!in_array($ct, $allowedImageTypes)) sendError('Tipo de imagen remoto no permitido.');
            }
            if ($contentLength !== null && (int)$contentLength > $maxImageBytes) sendError('Imagen remota demasiado grande.');

            $ctx = stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]);
            $c = @file_get_contents($url, false, $ctx);
            if ($c === false) sendError('No se pudo descargar la imagen remota.');
            if (strlen($c) > $maxImageBytes) sendError('Imagen remota demasiado grande.');

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detType = finfo_buffer($finfo, $c);
            // Eliminado: $finfo->close() no existe en FileInfo
            if (!in_array($detType, $allowedImageTypes)) sendError('Tipo de imagen remoto no permitido.');

            $img = $c;
        }
    }
    
    $legacyType = 'custom';

    if ($id) {
        // Verificar Permiso Edición (Admin/Mod editan todo, User solo suyo)
        $stmt=$conn->prepare("SELECT created_by FROM events WHERE id=?"); $stmt->execute([$id]); $ev=$stmt->fetch();
        $canEdit = ($userRole==='admin' || $userRole==='moderator' || $ev['created_by']==$userId);
        if(!$canEdit) sendError('Sin permiso para editar.');

        $sql="UPDATE events SET title=?, event_date=?, end_date=?, start_time=?, end_time=?, description=?, event_type_id=?, type=?, color=?, visibility=?, status=?";
        $p=[$title,$date,$endDate,$startTime,$endTime,$desc,$typeId,$legacyType,$color,$vis,$st];
        if(in_array($userRole,['admin','moderator'])){$sql.=", created_by=?";$p[]=$ownerId;}
        if($img){$sql.=", image=?";$p[]=$img;}
        $sql.=" WHERE id=?"; $p[]=$id;
        $conn->prepare($sql)->execute($p); sendSuccess('Actualizado');
    } else {
        $conn->prepare("INSERT INTO events (title, event_date, end_date, start_time, end_time, description, event_type_id, type, color, visibility, status, created_by, image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
             ->execute([$title,$date,$endDate,$startTime,$endTime,$desc,$typeId,$legacyType,$color,$vis,$st,$ownerId,$img]);
        sendSuccess('Creado');
    }
}

// 3. DELETE
if ($action === 'delete' && $method === 'POST') {
    $in = json_decode(file_get_contents('php://input'), true);
    $stmt=$conn->prepare("SELECT created_by FROM events WHERE id=?"); $stmt->execute([$in['id']]); $ev=$stmt->fetch();
    
    $canDel = ($userRole==='admin' || $userRole==='moderator' || $ev['created_by']==$userId);
    if($userStatus==='banned_create') $canDel = false;

    if($canDel) { $conn->prepare("DELETE FROM events WHERE id=?")->execute([$in['id']]); sendSuccess('Eliminado'); }
    else sendError('Sin permiso');
}

// 5. GET SINGLE EVENT
if ($action === 'get' && $method === 'GET') {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT e.*, u.nickname as creator_nickname FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);
    if($ev) sendJson(['success' => true, 'data' => $ev]);
    else sendError('Evento no encontrado');
}
?>