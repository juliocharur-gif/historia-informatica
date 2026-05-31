<?php
session_start();
header("X-Frame-Options:SAMEORIGIN");
header("X-Content-Type-Options:nosniff");
header("X-XSS-Protection:1; mode=block");
header("Strict-Transport-Security:max-age=31536000; includeSubDomains");

include("conexion.php");
$db=new Database(); 
$conn=$db->getConnection();

$error="";
if($_SERVER["REQUEST_METHOD"]=="POST"){
  $correo = filter_var(trim($_POST['correo']), FILTER_SANITIZE_EMAIL);
  $clave = trim($_POST['clave']);
  
  // 🔹 Buscar usuario por correo (más seguro que usuario)
  $stmt=$conn->prepare("SELECT id, nombre, correo, clave, rol FROM usuarios WHERE correo = ? LIMIT 1");
  if ($stmt === false) {
    die("❌ Error en prepare: " . $conn->error);
  }
  $stmt->bind_param("s", $correo);
  $stmt->execute();
  $resultado=$stmt->get_result();
  
  if($resultado && $resultado->num_rows===1){
    $row=$resultado->fetch_assoc();
    
    // 🔹 ✅ USAR password_verify() PARA COMPARAR HASH BCRYPT
    if(password_verify($clave, $row['clave'])){
      session_regenerate_id(true);
      $_SESSION['usuario_id']=$row['id'];
      $_SESSION['usuario']=$row['nombre'];
      $_SESSION['correo']=$row['correo'];
      $_SESSION['rol']=$row['rol'];
      
      if($row['rol']==='admin'){
        header("Location:dashboard.php");exit();
      } else {
        header("Location:index.php");exit();
      }
    } else {
      $error="❌ Correo o contraseña incorrecta.";
    }
  } else {
    $error="❌ Correo o contraseña incorrecta.";
  }
}
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Login - Historia Informática</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
body{
  margin:0;font-family:'Segoe UI',sans-serif;
  background:linear-gradient(135deg,#1e3c72,#2a5298);
  height:100vh;display:flex;justify-content:center;align-items:center;
  animation:fadePage 1.2s ease;
}
.login-box{
  background:#fff;padding:40px;border-radius:14px;
  box-shadow:0 8px 20px rgba(0,0,0,.25);
  width:90%;max-width:420px;text-align:center;
}
.login-box h2{margin-bottom:25px;color:#2a5298;font-size:28px;font-weight:bold;}
.form-group{margin-bottom:20px;text-align:left;}
label{display:block;margin-bottom:6px;font-weight:bold;color:#333;font-size:15px;}
input[type="email"],input[type="password"]{
  width:100%;padding:14px;border:1px solid #ccc;border-radius:8px;
  font-size:16px;transition:.3s;box-sizing:border-box;
}
input:focus{border-color:#2a5298;box-shadow:0 0 6px rgba(42,82,152,.4);outline:none;}
.show-pass{
  display:flex;align-items:center;gap:10px;margin-top:8px;
}
.show-pass input[type="checkbox"]{
  width:20px;height:20px;cursor:pointer;
}
.show-pass span{
  font-size:14px;color:#333;
}
button{
  width:100%;padding:14px;background:#2a5298;color:#fff;border:none;
  border-radius:8px;font-size:18px;font-weight:bold;cursor:pointer;transition:.3s;
}
button:hover{background:#1e3c72;transform:scale(1.03);}
.error{color:red;margin-bottom:15px;font-size:15px;font-weight:bold;}
.registro-link{display:block;margin-top:18px;color:#2a5298;font-weight:bold;text-decoration:none;font-size:15px;}
.registro-link:hover{text-decoration:underline;}
@keyframes fadePage{from{opacity:0;transform:scale(0.95);}to{opacity:1;transform:scale(1);}}
</style>
</head><body>
<div class="login-box">
  <h2>🔐 Iniciar Sesión</h2>
  <?php if(!empty($error)) echo "<p class='error'>$error</p>"; ?>
  <form method="POST">
    <div class="form-group">
      <label for="correo">Correo electrónico</label>
      <input type="email" name="correo" id="correo" placeholder="tu@email.com" required>
    </div>
    <div class="form-group">
      <label for="clave">Contraseña</label>
      <input type="password" name="clave" id="clave" placeholder="Tu contraseña" required>
      <div class="show-pass">
        <input type="checkbox" id="toggleClave" onclick="togglePassword()">
        <span>Mostrar contraseña</span>
      </div>
    </div>
    <button type="submit">Ingresar</button>
  </form>
  <a href="registro.php" class="registro-link">¿No tienes cuenta? Regístrate aquí</a>
</div>
<script>
function togglePassword(){
  const clave=document.getElementById("clave");
  clave.type=clave.type==="password"?"text":"password";
}
</script>
</body></html>