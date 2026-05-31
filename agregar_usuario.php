<?php
// ==========================
// 🔹 Sección: Seguridad
// ==========================
session_start();
header("X-Frame-Options: SAMEORIGIN");        
header("X-Content-Type-Options: nosniff");    
header("X-XSS-Protection: 1; mode=block");    
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php");
    exit();
}
session_regenerate_id(true);

include("conexion.php");
$db = new Database();
$conn = $db->getConnection();

// ==========================
// 🔹 Insertar usuario
// ==========================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre  = htmlspecialchars(trim($_POST['nombre']));
    $correo  = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $clave   = trim($_POST['clave']);
    $rol     = htmlspecialchars(trim($_POST['rol']));

    // 🔹 Validar contraseña (mínimo 8 caracteres)
    if (strlen($clave) < 8) {
        $error = "❌ La contraseña debe tener al menos 8 caracteres.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El correo no es válido.";
    } else {
        // 🔹 Verificar si el usuario ya existe
        $sql_check = "SELECT id FROM usuarios WHERE correo = ? LIMIT 1";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check === false) {
            die("❌ Error en prepare (CHECK): " . $conn->error);
        }
        $stmt_check->bind_param("s", $correo);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();

        if ($resultado->num_rows > 0) {
            $error = "❌ El correo ya está registrado.";
        } else {
            // 🔹 ✅ Hash seguro de contraseña con BCRYPT
            $clave_hash = password_hash($clave, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (nombre, correo, clave, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("❌ Error en prepare (INSERT): " . $conn->error);
            }
            $stmt->bind_param("ssss", $nombre, $correo, $clave_hash, $rol);

            if ($stmt->execute()) {
                $mensaje = "✅ Usuario agregado correctamente.";
            } else {
                $error = "❌ Error al agregar usuario: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Usuario</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:'Segoe UI',sans-serif;margin:0;color:#333;
  background:linear-gradient(-45deg,#1e3c72,#2a5298,#38ef7d,#ff6b6b);
  background-size:400% 400%;animation:gradientBG 6s ease infinite;}
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
header{background:rgba(0,0,0,.6);padding:25px;text-align:center;color:#fff;font-size:26px;font-weight:bold;}
nav{background:#2c3e50;padding:12px;display:flex;flex-wrap:wrap;justify-content:center;gap:15px;}
nav a{color:#fff;text-decoration:none;font-weight:bold;transition:.3s;font-size:16px;padding:8px 12px;border-radius:6px;background:#1e3c72;}
nav a:hover{background:#38ef7d;color:#000;}
.container{padding:25px;max-width:700px;margin:auto;}
form{background:#fff;padding:20px;border-radius:12px;box-shadow:0 6px 14px rgba(0,0,0,0.1);box-sizing:border-box;}
label{display:block;margin-top:15px;font-weight:bold;font-size:18px;}
input,select{width:100%;max-width:100%;padding:12px;margin-top:8px;border:1px solid #ccc;border-radius:6px;font-size:15px;box-sizing:border-box;}
button{margin-top:20px;padding:14px 24px;border:none;border-radius:8px;background:#3498db;color:#fff;font-size:18px;cursor:pointer;transition:.3s;width:100%;}
button:hover{opacity:0.9;transform:scale(1.05);}
.mensaje{margin-top:15px;font-weight:bold;font-size:18px;}
a.back-btn{display:block;margin:25px auto;padding:16px 28px;background:#e74c3c;color:#fff;text-align:center;font-size:18px;font-weight:bold;border-radius:8px;text-decoration:none;max-width:300px;box-shadow:0 4px 8px rgba(0,0,0,0.3);transition:.3s;}
a.back-btn:hover{background:#c0392b;transform:scale(1.05);}
</style>
</head>
<body>
<header>➕ Agregar Usuario</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="gestionar_tematicas.php">📚 Temáticas</a>
  <a href="gestionar_eventos.php">📅 Eventos</a>
  <a href="gestionar_ia.php">🤖 IA</a>
  <a href="logout.php">🚪 Cerrar sesión</a>
</nav>

<div class="container">
  <?php if(isset($mensaje)) echo "<p class='mensaje' style='color:green;'>$mensaje</p>"; ?>
  <?php if(isset($error)) echo "<p class='mensaje' style='color:red;'>$error</p>"; ?>

  <form method="POST" action="">
    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" id="nombre" required>

    <label for="correo">Correo:</label>
    <input type="email" name="correo" id="correo" required>

    <label for="clave">Contraseña (mínimo 8 caracteres):</label>
    <input type="password" name="clave" id="clave" required>

    <label for="rol">Rol:</label>
    <select name="rol" id="rol" required>
      <option value="">-- Selecciona un rol --</option>
      <option value="admin">Administrador</option>
      <option value="visitante">Visitante</option>
    </select>

    <button type="submit">Guardar Usuario</button>
  </form>

  <a href="gestionar_usuarios.php" class="back-btn">⬅️ Volver al listado</a>
</div>
</body>
</html>