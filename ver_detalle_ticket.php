<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

if (!isset($_GET['id'])) {
    header("Location: detalle_queja_usuario.php");
    exit();
}

$id_ticket = intval($_GET['id']);

// Consultamos todos los datos del ticket. Verificamos que sea de este id_usuario por seguridad.
$query = "SELECT t.*, tp.nombre_tipo 
          FROM tickets_ayuda t
          INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
          WHERE t.id_ticket = '$id_ticket' AND t.id_usuario = '$id_usuario'";

$result = mysqli_query($cn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "<h2>No se encontró el ticket o no tienes permisos para verlo.</h2>";
    echo "<a href='detalle_queja_usuario.php'>Volver</a>";
    exit();
}

$ticket = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Ticket | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .detalle-caja { background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e0e0e0; margin-bottom: 20px;}
        .detalle-item { margin-bottom: 15px; }
        .detalle-label { font-weight: bold; color: #555; display: block; font-size: 0.9rem; margin-bottom: 5px; }
        .detalle-valor { font-size: 1rem; color: #222; background: #f9f9f9; padding: 10px; border-radius: 4px; border-left: 4px solid var(--azul-institucional); }
        .resolucion-caja { background: #eef9f0; border-left: 4px solid #28a745; padding: 15px; border-radius: 4px; margin-top: 20px;}
        .resolucion-label { font-weight: bold; color: #155724; font-size: 1rem; margin-bottom: 10px; display:block; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="detalle_queja_usuario.php">Volver a mis Reclamos</a>
        </div>
    </nav>

    <div class="container-sm" style="max-width: 800px; margin: 40px auto;">
        <div class="panel">
            <h2 class="panel-title">Detalles del Reporte: <?php echo htmlspecialchars($ticket['codigo_ticket']); ?></h2>
            
            <div class="detalle-caja">
                <div class="detalle-item">
                    <span class="detalle-label">Tipo de Reporte:</span>
                    <div class="detalle-valor"><?php echo htmlspecialchars($ticket['nombre_tipo']); ?></div>
                </div>
                
                <div class="detalle-item">
                    <span class="detalle-label">Asunto:</span>
                    <div class="detalle-valor"><?php echo htmlspecialchars($ticket['asunto']); ?></div>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Descripción Detallada que usted envió:</span>
                    <div class="detalle-valor" style="white-space: pre-wrap;"><?php echo htmlspecialchars($ticket['descripcion_problema']); ?></div>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Fecha de Registro:</span>
                    <div class="detalle-valor"><?php echo date("d/m/Y h:i A", strtotime($ticket['fecha_registro'])); ?></div>
                </div>

                <div class="detalle-item">
                    <span class="detalle-label">Estado Actual:</span>
                    <div class="detalle-valor" style="font-weight: bold;">
                        <?php echo htmlspecialchars(strtoupper($ticket['estado_ticket'])); ?>
                    </div>
                </div>

                <!-- Si el ticket está atendido, mostramos la resolución dictada por la administración -->
                <?php if (strcasecmp($ticket['estado_ticket'], 'Atendido') == 0 && !empty($ticket['respuesta'])): ?>
                    <div class="resolucion-caja">
                        <span class="resolucion-label">Respuesta / Resolución de Administración:</span>
                        <p style="margin:0; color: #155724; line-height: 1.5; white-space: pre-wrap;"><?php echo htmlspecialchars($ticket['respuesta']); ?></p>
                    </div>
                <?php elseif (strcasecmp($ticket['estado_ticket'], 'Atendido') == 0): ?>
                    <div class="resolucion-caja">
                        <span class="resolucion-label">Respuesta / Resolución de Administración:</span>
                        <p style="margin:0; color: #155724;">Su reporte ha sido revisado y procesado exitosamente por nuestra oficina de atención.</p>
                    </div>
                <?php else: ?>
                    <p style="margin-top: 20px; font-size: 0.9rem; color: #888; font-style: italic;">Su solicitud se encuentra actualmente en proceso. Recibirá una respuesta en este mismo panel una vez que sea atendida.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>