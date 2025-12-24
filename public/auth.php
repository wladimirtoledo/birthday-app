<?php
// public/auth.php
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'login';

    $db = new Database();
    $conn = $db->getConnection();

    // =========================================================
    // LOGIN
    // =========================================================
    if ($action === 'login') {
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'Complete todos los campos.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role, status FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // REGLA: Si el usuario está bloqueado (o recién registrado sin aprobar)
            if ($user['status'] === 'banned_login') {
                echo json_encode(['success' => false, 'message' => 'Tu cuenta está pendiente de aprobación o bloqueada. Contacta al administrador.']);
                exit;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_status'] = $user['status'];

            echo json_encode(['success' => true, 'message' => 'Login exitoso', 'data' => ['redirect' => 'index.php']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
        }
    }

    // =========================================================
    // REGISTRO (AHORA CREA USUARIOS BLOQUEADOS)
    // =========================================================
    elseif ($action === 'register') {
        $first = trim($input['first_name'] ?? '');
        $last = trim($input['last_name'] ?? '');
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $pass = $input['password'] ?? '';
        $birth = $input['birthdate'] ?? null;

        if (empty($first) || empty($last) || empty($email) || empty($pass)) {
            echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
            exit;
        }

        // Verificar duplicado
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Este correo ya está registrado.']);
            exit;
        }

        // CAMBIO AQUÍ: Estado por defecto es 'banned_login' en lugar de 'active'
        $sql = "INSERT INTO users (first_name, last_name, email, password, role, status, birthdate) 
                VALUES (?, ?, ?, ?, 'user', 'banned_login', ?)";
        
        try {
            $stmt = $conn->prepare($sql);
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt->execute([$first, $last, $email, $hash, $birth]);
            
            echo json_encode(['success' => true, 'message' => 'Registro exitoso. Tu cuenta está pendiente de aprobación.']);
        } catch (Exception $e) {
            error_log('Register error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al registrar usuario. Intente nuevamente más tarde.']);
        }
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>