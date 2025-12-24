<?php
// public/api/common.php

// 1. Limpieza de Buffer y Errores (CRÍTICO PARA EVITAR ERRORES DE PARSEO)
ob_start(); // Iniciar buffer de salida
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla (rompen el JSON)
ini_set('log_errors', 1);     // Guardarlos en log del servidor

// 2. Headers JSON
header('Content-Type: application/json; charset=utf-8');

// 3. Incluir configuración
// Ajusta la ruta '../config/db.php' según dónde esté tu archivo real relativo a esta carpeta
require_once __DIR__ . '/../../config/db.php'; 
require_once __DIR__ . '/../../includes/functions.php';

// 4. Configuración Global
ini_set('memory_limit', '512M');

// 5. Inicializar Conexión Global
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Error de Conexión BD: ' . $e->getMessage()]);
    exit;
}

// 6. Sesión Global
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['user_role'] ?? 'guest';

// 7. Helpers de Respuesta
function sendJson($data) {
    ob_clean(); // Limpiar cualquier echo anterior o warning
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError($message) {
    sendJson(['success' => false, 'message' => $message]);
}

function sendSuccess($message, $data = []) {
    sendJson(array_merge(['success' => true, 'message' => $message], $data));
}
?>