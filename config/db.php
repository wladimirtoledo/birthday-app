<?php
// config/db.php
declare(strict_types=1);

class Database {
    private string $host = 'localhost';
    private string $db_name = 'birthday_app';
    private string $username = 'root'; // Cambiar en producción
    private string $password = '';     // Cambiar en producción
    public ?PDO $conn = null;

    public function getConnection(): PDO {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Configuración de Seguridad y Errores
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // Prevenir inyección SQL real
            
        } catch(PDOException $exception) {
            // En producción, registra esto en un log, no lo muestres al usuario
            error_log("Connection error: " . $exception->getMessage());
            die("Error crítico de conexión. Contacte al administrador.");
        }
        return $this->conn;
    }
}
?>