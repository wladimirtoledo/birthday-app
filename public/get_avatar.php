<?php
require_once '../config/db.php';
// Este script sirve la imagen directamente como si fuera un archivo jpg/png

if (isset($_GET['id'])) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && !empty($row['avatar'])) {
        header("Content-Type: image/jpeg"); // Asumimos jpeg, o detectamos
        echo $row['avatar'];
        exit;
    }
}

// Si no hay imagen, redirigir a un placeholder o devolver vacío
// (El frontend manejará el error con onerror)
//http_response_code(404);
?>