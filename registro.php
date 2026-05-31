<?php
// ==========================
// 🔹 Registro de usuarios seguro
// ==========================
session_start();
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

include("conexion.php");
$db = new Database();
$conn = $db->getConnection();

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre  = htmlspecialchars(trim($_POST['nombre']));
    $correo  = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
    $clave   = trim($_POST['clave']);
    $clave_conf = trim($_POST['clave_confirmacion']);

    // 🔹 Validaciones
    if (strlen($nombre) < 3) {
        $error = "❌ El nombre debe tener al menos 3 caracteres.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ El correo no es válido.";
    } elseif (strlen($clave) < 8) {
        $error = "❌ La contraseña debe tener al menos 8 caracteres.";
    } elseif ($clave !== $clave_conf) {
        $error = "❌ Las contraseñas no coinciden.";
    } else {
        // 🔹 Verificar que no exista el correo
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

            // 🔹 Los usuarios nuevos siempre son "visitante" (por seguridad)
            $rol = "visitante";

            $sql = "INSERT INTO usuarios (nombre, correo, clave, rol) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("❌ Error en prepare (INSERT): " . $conn->error);
            }
            $stmt->bind_param("ssss", $nombre, $correo, $clave_hash, $rol);
            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora puedes iniciar sesión.";
            } else {
                $error = "❌ Error al registrar: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><title>Registro - Historia Informática</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#11998e,#38ef7d);height:100vh;display:flex;justify-content:center;align-items:center;animation:fadePage 1.2s ease;}
.registro-box{background:#fff;padding:30px;border-radius:12px;box-shadow:0 6px 15px rgba(0,0,0,.3);width:90%;max-width:420px;text-align:center;box-sizing:border-box;}
.registro-box h2{margin-bottom:20px;color:#11998e;font-size:26px;}
input{width:100%;padding:14px;margin:10px 0;border:1px solid #ccc;border-radius:8px;font-size:18px;box-sizing:border-box;}
input:focus{border-color:#11998e;box-shadow:0 0 8px rgba(17,153,142,.5);}
.show-pass{text-align:left;font-size:14px;color:#333;margin:5px 0;}
button{width:100%;padding:14px;background:#11998e;color:#fff;border:none;border-radius:8px;font-size:20px;cursor:pointer;transition:.3s;box-sizing:border-box;}
button:hover{background:#38ef7d;transform:scale(1.05);}
.mensaje{color:green;margin-bottom:15px;font-size:16px;font-weight:bold;}
.error{color:red;margin-bottom:15px;font-size:16px;font-weight:bold;}
.login-link{display:block;margin-top:15px;color:#11998e;font-weight:bold;text-decoration:none;font-size:16px;}
.login-link:hover{text-decoration:underline;}
@media(max-width:600px){.registro-box{padding:20px;}input,button{font-size:18px;}}
@keyframes fadePage{from{opacity:0;transform:scale(0.95);}to{opacity:1;transform:scale(1);}}
</style>
<script>
function togglePassword(){
  var input=document.getElementById("clave");
  input.type=(input.type==="password")?"text":"password";
}
function togglePasswordConf(){
  var input=document.getElementById("clave_confirmacion");
  input.type=(input.type==="password")?"text":"password";
}
</script>
</head>
<body>
<div class="registro-box">
  <h2>📝 Registro de Usuario</h2>
  <?php if(!empty($mensaje)) echo "<p class='mensaje'>$mensaje</p>"; ?>
  <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
  <form method="POST" action="">
    <input type="text" name="nombre" placeholder="Nombre completo (mín. 3 caracteres)" minlength="3" required>
    <input type="email" name="correo" placeholder="Correo electrónico" required>
    <label style="text-align:left;margin-top:10px;font-weight:bold;">Contraseña (mínimo 8 caracteres):</label>
    <input type="password" id="clave" name="clave" placeholder="Contraseña" minlength="8" required>
    <div class="show-pass">
      <input type="checkbox" id="showPass" onclick="togglePassword()">
      <label for="showPass">Mostrar contraseña</label>
    </div>
    <label style="text-align:left;margin-top:10px;font-weight:bold;">Confirmar contraseña:</label>
    <input type="password" id="clave_confirmacion" name="clave_confirmacion" placeholder="Confirma tu contraseña" minlength="8" required>
    <div class="show-pass">
      <input type="checkbox" id="showPassConf" onclick="togglePasswordConf()">
      <label for="showPassConf">Mostrar confirmación</label>
    </div>
    <button type="submit">Registrarse</button>
  </form>
  <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión aquí</a>
</div>
</body>
</html>