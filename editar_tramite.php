<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: gestion_tramites.php");
    exit();
}

$id = intval($_GET['id']);
$query = "SELECT * FROM tipos_tramite WHERE id_tipo_tramite = $id";
$resultado = mysqli_query($cn, $query);
$tramite = mysqli_fetch_assoc($resultado);

if (!$tramite) {
    die("Trámite no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Trámite | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'cabecera_admin.php'; ?>
    <div class="container">
        <a href="gestion_tramites.php" class="btn-volver">← Cancelar y Volver</a>
        <div class="panel" style="margin-top: 15px; max-width: 600px;">
            <h2 class="panel-title panel-title-clean">Editar Nombre de Trámite</h2>
            <form action="procesar_tramite.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" name="id_tipo_tramite" value="<?= $tramite['id_tipo_tramite'] ?>">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Nombre del Trámite:</label>
                    <input type="text" name="nombre_tramite" value="<?= htmlspecialchars($tramite['nombre_tramite']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <button type="submit" class="btn-accion status-atendido" style="border: none; cursor: pointer; padding: 10px 20px; font-size: 1rem;">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>