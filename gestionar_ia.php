<?php
session_start();
if(!isset($_SESSION['usuario'])||$_SESSION['rol']!=='admin'){header("Location:index.php");exit();}
session_regenerate_id(true);

include("conexion.php");
$db=new Database(); 
$conexion=$db->getConnection();

// Eliminar registro
if(isset($_GET['eliminar'])){
  $id=intval($_GET['eliminar']);
  $stmt = $conexion->prepare("DELETE FROM ia WHERE id = ?");
  if ($stmt === false) {
    die("❌ Error en prepare: " . $conexion->error);
  }
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: gestionar_ia.php?msg=eliminado"); 
  exit();
}

// Registrar nuevo
if(isset($_POST['registrar'])){
  $titulo = htmlspecialchars(trim($_POST['titulo']));
  $descripcion = htmlspecialchars(trim($_POST['descripcion']));
  $fecha = trim($_POST['fecha']);
  
  $stmt = $conexion->prepare("INSERT INTO ia(titulo, descripcion, fecha) VALUES(?, ?, ?)");
  if ($stmt === false) {
    die("❌ Error en prepare: " . $conexion->error);
  }
  $stmt->bind_param("sss", $titulo, $descripcion, $fecha);
  if ($stmt->execute()) {
    header("Location: gestionar_ia.php?msg=registrado"); 
    exit();
  } else {
    $error = "❌ Error: " . $conexion->error;
  }
}

// Obtener registros
$stmt = $conexion->prepare("SELECT * FROM ia ORDER BY fecha DESC");
if ($stmt === false) {
  die("❌ Error en prepare: " . $conexion->error);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><title>Gestionar IA</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
body{font-family:'Segoe UI';margin:0;color:#333;
  background:linear-gradient(-45deg,#1e3c72,#2a5298,#38ef7d,#ff6b6b);
  background-size:400% 400%;animation:gradientBG 10s ease infinite;}
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
header{background:rgba(0,0,0,.6);padding:20px;text-align:center;color:#fff;font-size:24px;font-weight:bold;}
nav{background:#2c3e50;padding:12px;text-align:center;}
nav a{color:#fff;margin:0 12px;text-decoration:none;font-weight:bold;transition:.3s;}
nav a:hover{color:#38ef7d;}
.container{padding:20px;max-width:900px;margin:auto;}
.titulo{color:#fff;padding:12px;border-radius:10px;text-align:center;font-size:22px;font-weight:bold;
  background:linear-gradient(270deg,#1e3c72,#2a5298,#38ef7d,#ff6b6b);
  background-size:800% 800%;animation:gradienteTitulo 6s ease infinite;box-shadow:0 4px 10px rgba(0,0,0,.3);}
@keyframes gradienteTitulo{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
form{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.2);margin:20px 0;box-sizing:border-box;}
input,textarea,button{
  width:100%;
  max-width:100%;
  box-sizing:border-box;
}
input,textarea{
  padding:12px;margin:8px 0;border:1px solid #ccc;border-radius:6px;font-size:15px;
}
textarea{min-height:100px;}
button{
  padding:12px;background:#2a5298;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:15px;
}
button:hover{background:#38ef7d;color:#000;}
.card{background:#fff;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.2);padding:15px;margin-bottom:15px;}
.card p{margin:6px 0;font-size:14px;}
.card strong{color:#2a5298;}
.acciones{display:flex;flex-direction:column;gap:8px;margin-top:10px;}
a.btn{padding:10px;border-radius:6px;text-decoration:none;color:#fff;text-align:center;font-size:14px;}
.btn-edit{background:#f39c12;} .btn-edit:hover{background:#d35400;}
.btn-delete{background:#e74c3c;} .btn-delete:hover{background:#c0392b;}
a.back-btn{display:block;margin:25px auto;padding:14px;background:#e74c3c;color:#fff;text-align:center;font-size:16px;font-weight:bold;border-radius:8px;text-decoration:none;max-width:250px;box-shadow:0 4px 8px rgba(0,0,0,.3);}
a.back-btn:hover{background:#c0392b;}
@media(min-width:768px){.acciones{flex-direction:row;}a.btn{flex:1;}}
.msg{font-weight:bold;text-align:center;margin:10px;}
</style></head><body>
<header>📊 Panel de Administración</header>
<nav>
  <a href="gestionar_usuarios.php">👥 Usuarios</a>
  <a href="gestionar_tematicas.php">📚 Temáticas</a>
  <a href="gestionar_eventos.php">📅 Eventos</a>
  <a href="logout.php">🚪 Cerrar sesión</a>
</nav>

<div class="container">
  <?php if(isset($_GET['msg'])&&$_GET['msg']=="eliminado") echo "<p class='msg' style='color:red;'>🗑️ Artículo eliminado correctamente.</p>"; ?>
  <?php if(isset($_GET['msg'])&&$_GET['msg']=="registrado") echo "<p class='msg' style='color:green;'>✅ Artículo registrado correctamente.</p>"; ?>
  <?php if(isset($error)) echo "<p class='msg' style='color:red;'>$error</p>"; ?>

  <h2 class="titulo">Registrar nuevo artículo de IA</h2>
  <form method="post">
    <input type="text" name="titulo" placeholder="Título" required>
    <textarea name="descripcion" placeholder="Descripción detallada" required></textarea>
    <input type="date" name="fecha">
    <button type="submit" name="registrar">Registrar</button>
  </form>

  <h2 class="titulo">Listado de artículos IA</h2>
  <?php while($fila=$resultado->fetch_assoc()): ?>
  <div class="card">
    <p><strong>ID:</strong> <?= htmlspecialchars($fila['id']) ?></p>
    <p><strong>Título:</strong> <?= htmlspecialchars($fila['titulo']) ?></p>
    <p><strong>Descripción:</strong> <?= htmlspecialchars(substr($fila['descripcion'],0,100)) ?>...</p>
    <p><strong>Fecha:</strong> <?= htmlspecialchars($fila['fecha']) ?></p>
    <div class="acciones">
      <a href="editar_ia.php?id=<?= intval($fila['id']) ?>" class="btn btn-edit">✏️ Editar</a>
      <a href="gestionar_ia.php?eliminar=<?= intval($fila['id']) ?>" class="btn btn-delete" onclick="return confirm('¿Eliminar este registro?')">🗑️ Eliminar</a>
    </div>
  </div>
  <?php endwhile; ?>

  <a href="dashboard.php" class="back-btn">⬅️ Volver al Dashboard</a>
</div>
</body></html>