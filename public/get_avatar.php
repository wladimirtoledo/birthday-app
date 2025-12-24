<?php
require_once '../config/db.php';
// Este script sirve la imagen directamente como si fuera un archivo jpg/png

if (isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id === false) {
        http_response_code(400);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['avatar'])) {
        // Detectar tipo MIME real del blob
        $blob = $row['avatar'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $blob);
        finfo_close($finfo);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowed)) $mime = 'application/octet-stream';
        header("Content-Type: " . $mime);
        echo $blob;
        exit;
    }
}

// Si no hay imagen, redirigir a un placeholder o devolver vacío
// (El frontend manejará el error con onerror)
//http_response_code(404);
?>