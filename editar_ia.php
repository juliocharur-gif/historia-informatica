<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php"); exit();
}
session_regenerate_id(true);
include("conexion.php");
$db = new Database(); 
$conexion = $db->getConnection();

$id = intval($_GET['id'] ?? 0);
$stmt = $conexion->prepare("SELECT * FROM ia WHERE id = ?");
if ($stmt === false) {
    die("❌ Error en prepare: " . $conexion->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo "❌ Registro no encontrado."; 
    exit();
}
$fila = $res->fetch_assoc();

$mensaje = "";
$error = "";
if (isset($_POST['actualizar'])) {
    $titulo = htmlspecialchars(trim($_POST['titulo']));
    $descripcion = htmlspecialchars(trim($_POST['descripcion']));
    $fecha = trim($_POST['fecha']);
    $imagen = $fila['imagen'];

    // 🔹 Manejo seguro de archivos
    if (!empty($_FILES['imagen']['name'])) {
        $file = $_FILES['imagen'];
        $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $permitidos)) {
            $error = "❌ Solo se permiten imágenes (JPG, PNG, GIF, WEBP).";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $error = "❌ La imagen no debe superar 5MB.";
        } else {
            $nombre = time() . "_" . basename($file['name']);
            $destino = "uploads/" . $nombre;
            
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                $imagen = $destino;
            } else {
                $error = "❌ Error al subir la imagen.";
            }
        }
    }

    if (!isset($error) || empty($error)) {
        $stmt = $conexion->prepare("UPDATE ia SET titulo = ?, descripcion = ?, fecha = ?, imagen = ? WHERE id = ?");
        if ($stmt === false) {
            die("❌ Error en prepare: " . $conexion->error);
        }
        $stmt->bind_param("ssssi", $titulo, $descripcion, $fecha, $imagen, $id);
        if ($stmt->execute()) {
            $mensaje = "✅ Artículo actualizado correctamente.";
            $fila['titulo'] = $titulo;
            $fila['descripcion'] = $descripcion;
            $fila['fecha'] = $fecha;
            $fila['imagen'] = $imagen;
        } else {
            $error = "❌ Error: " . $conexion->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><title>Editar IA</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{
  font-family:'Segoe UI',sans-serif;margin:0;color:#333;
  background:linear-gradient(-45deg,#1e3c72,#2a5298,#38ef7d,#ff6b6b);
  background-size:400% 400%;
  animation:gradientBG 10s ease infinite;
}
@keyframes gradientBG{
  0%{background-position:0% 50%;}
  50%{background-position:100% 50%;}
  100%{background-position:0% 50%;}
}
header{background:rgba(0,0,0,.6);padding:15px;text-align:center;color:#fff;font-size:22px;font-weight:bold;}
form{background:#fff;padding:20px;border-radius:10px;box-shadow:0 4px 8px rgba(0,0,0,.2);max-width:600px;margin:20px auto;box-sizing:border-box;}
label{font-weight:bold;} 
input,textarea{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:6px;box-sizing:border-box;}
textarea{min-height:120px;} 
button{padding:12px;background:#2a5298;color:#fff;border:none;border-radius:6px;cursor:pointer;width:100%;}
button:hover{background:#38ef7d;color:#000;} 
img.preview{max-width:100%;border-radius:8px;margin:10px 0;}
.msg{color:lightgreen;font-weight:bold;text-align:center;margin:10px;}
.error-msg{color:red;font-weight:bold;text-align:center;margin:10px;}
a.back-btn{display:block;text-align:center;margin:15px auto;padding:12px;background:#e74c3c;color:#fff;border-radius:6px;text-decoration:none;max-width:250px;}
a.back-btn:hover{background:#c0392b;}
nav{background:#2c3e50;padding:12px;display:flex;flex-wrap:wrap;justify-content:center;gap:15px;}
nav a{color:#fff;text-decoration:none;font-weight:bold;transition:.3s;font-size:16px;padding:8px 12px;border-radius:6px;background:#1e3c72;}
nav a:hover{background:#38ef7d;color:#000;}
</style>
</head>
<body>
<header>📊 Editar artículo de IA</header>
<nav>
  <a href="dashboard.php">🏠 Dashboard</a>
  <a href="gestionar_tematicas.php">📚 Temáticas</a>
  <a href="gestionar_eventos.php">📅 Eventos</a>
  <a href="logout.php">🚪 Cerrar sesión</a>
</nav>
<?php if($mensaje): ?><p class="msg"><?= $mensaje ?></p><?php endif; ?>
<?php if($error): ?><p class="error-msg"><?= $error ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <label>Título:</label>
  <input type="text" name="titulo" value="<?= htmlspecialchars($fila['titulo']) ?>" required>
  <label>Descripción:</label>
  <textarea name="descripcion" required><?= htmlspecialchars($fila['descripcion']) ?></textarea>
  <label>Fecha:</label>
  <input type="date" name="fecha" value="<?= htmlspecialchars($fila['fecha']) ?>">
  <label>Imagen (JPG, PNG, GIF, WEBP - máx 5MB):</label>
  <input type="file" name="imagen" accept="image/*">
  <?php if(!empty($fila['imagen'])): ?>
    <img src="<?= htmlspecialchars($fila['imagen']) ?>" class="preview" alt="Imagen actual">
  <?php endif; ?>
  <button type="submit" name="actualizar">Actualizar</button>
</form>

<a href="gestionar_ia.php" class="back-btn">⬅️ Volver al listado</a>
</body>
</html>