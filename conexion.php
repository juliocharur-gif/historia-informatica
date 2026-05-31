<?php
// ==========================
// 🔐 CONEXIÓN SEGURA A BD
// ==========================

class Database {
    private $host;
    private $usuario;
    private $clave;
    private $bd;
    private $conn;

    public function __construct() {
        // 🔹 Intentar cargar variables de entorno desde .env.local
        if (file_exists(__DIR__ . '/.env.local')) {
            $env = parse_ini_file(__DIR__ . '/.env.local');
            $this->host = $env['DB_HOST'] ?? 'localhost';
            $this->usuario = $env['DB_USER'] ?? 'root';
            $this->clave = $env['DB_PASS'] ?? '';
            $this->bd = $env['DB_NAME'] ?? 'historia_informatica';
        } else {
            // Valores por defecto para desarrollo local
            $this->host = "localhost";
            $this->usuario = "root";
            $this->clave = "";
            $this->bd = "historia_informatica";
        }
    }

    // 🔹 Método para abrir conexión
    public function getConnection() {
        if ($this->conn == null) {
            $this->conn = new mysqli($this->host, $this->usuario, $this->clave, $this->bd);

            if ($this->conn->connect_error) {
                // No mostrar detalles del error en producción
                error_log("Error de conexión BD: " . $this->conn->connect_error);
                die("❌ Error de conexión a la base de datos. Contacta al administrador.");
            }

            // Configurar charset para evitar problemas con acentos
            $this->conn->set_charset("utf8mb4");
        }
        return $this->conn;
    }

    // 🔹 Método para cerrar conexión
    public function closeConnection() {
        if ($this->conn != null) {
            $this->conn->close();
            $this->conn = null;
        }
    }
}
?>