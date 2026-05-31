<?php
// ==========================
// 🔐 BACKUP SEGURO DE BD
// ==========================
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    die("❌ Acceso denegado.");
}
session_regenerate_id(true);

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// ==========================
// 🔹 Obtener conexión desde clase
// ==========================
include("conexion.php");
$db = new Database();
$conn = $db->getConnection();

// ==========================
// 🔹 Generar respaldo SQL
// ==========================
$fecha = date("Y-m-d_H-i-s");
$nombreArchivo = "backup_historia_informatica_$fecha.sql";

$sqlDump = "-- ==========================================\n";
$sqlDump .= "-- Respaldo de Base de Datos\n";
$sqlDump .= "-- Fecha: " . date("Y-m-d H:i:s") . "\n";
$sqlDump .= "-- ==========================================\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Obtener todas las tablas
$tablas = $conn->query("SHOW TABLES");
if (!$tablas) {
    die("❌ Error al obtener tablas: " . $conn->error);
}

while ($fila = $tablas->fetch_array()) {
    $tabla = $fila[0];

    // Crear estructura
    $res = $conn->query("SHOW CREATE TABLE `$tabla`");
    if (!$res) {
        die("❌ Error al obtener estructura de `$tabla`: " . $conn->error);
    }
    
    $row = $res->fetch_assoc();
    $sqlDump .= "-- ==========================================\n";
    $sqlDump .= "-- Estructura de tabla `$tabla`\n";
    $sqlDump .= "-- ==========================================\n";
    $sqlDump .= "DROP TABLE IF EXISTS `$tabla`;\n";
    $sqlDump .= $row['Create Table'] . ";\n\n";

    // Insertar datos
    $datos = $conn->query("SELECT * FROM `$tabla`");
    if ($datos && $datos->num_rows > 0) {
        $sqlDump .= "-- ==========================================\n";
        $sqlDump .= "-- Datos de tabla `$tabla`\n";
        $sqlDump .= "-- ==========================================\n";
        
        while ($r = $datos->fetch_assoc()) {
            $valores = array_map(function($v) use ($conn) {
                if (is_null($v)) {
                    return "NULL";
                }
                return "'" . addslashes($v) . "'";
            }, $r);
            
            $sqlDump .= "INSERT INTO `$tabla` VALUES(" . implode(",", $valores) . ");\n";
        }
        $sqlDump .= "\n";
    }
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
$sqlDump .= "\n-- Fin del respaldo\n";

// ==========================
// 🔹 Descargar respaldo (seguro)
// ==========================
header("Content-Disposition: attachment; filename=$nombreArchivo");
header("Content-Type: application/sql; charset=utf-8");
header("Content-Length: " . strlen($sqlDump));
header("Pragma: no-cache");
header("Expires: 0");

echo $sqlDump;
exit;
?>