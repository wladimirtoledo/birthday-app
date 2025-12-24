<?php
// includes/functions.php
session_start();

// Sanitización de outputs (XSS Protection)
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Middleware para verificar autenticación
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Middleware para verificar roles (RBAC)
function hasRole($roles = []) {
    if (!in_array($_SESSION['user_role'], $roles)) {
        return false;
    }
    return true;
}

// Respuesta JSON estándar
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
?>