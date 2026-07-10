<?php
session_start();

// Validamos que sea personal administrativo (ajusta la variable de sesión según tu lógica de login_mesa)
if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

// Consulta optimizada para traer tickets, tipos de ticket y datos del usuario (natural o jurídico)
$query_tickets = "
    SELECT t.id_ticket, t.codigo_ticket, t.asunto, t.descripcion_problema, t.estado_ticket, t.fecha_registro,
           tt.nombre_tipo,
           u.correo,
           COALESCE(CONCAT(dp.nombres, ' ', dp.apellido_paterno), dj.razon_social, 'Usuario Desconocido') AS remitente,
           COALESCE(dp.celular, dj.correo_empresarial, 'Sin contacto') AS dato_contacto
    FROM tickets_ayuda t
    INNER JOIN tipos_ticket tt ON t.id_tipo_ticket = tt.id_tipo_ticket
    INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
    LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
    LEFT JOIN datos_juridicos dj ON u.id_usuario = dj.id_usuario
    ORDER BY 
        CASE WHEN t.estado_ticket = 'Abierto' THEN 1 ELSE 2 END, 
        t.fecha_registro DESC
";

$res_tickets = mysqli_query($cn, $query_tickets);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja Mesa de Ayuda | Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .badge-sugerencia { background-color: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-queja { background-color: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-reclamo { background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .row-details { background-color: #f8f9fa; display: none; padding: 15px; border-top: 1px dashed #ccc; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.85rem; }
        .btn-atender { background-color: #28a745; color: white; }
        .btn-atender:hover { background-color: #218838; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Admin Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal_mesa.php">Bandeja de Trámites</a>
            <a href="mesa_bandeja_ayuda.php" class="active">Mesa de Ayuda</a>
            <a href="cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container" style="max-width: 1200px;">
        <div class="panel">
            <h2 class="panel-title">Bandeja de Recepción: Mesa de Ayuda</h2>
            <p style="margin-bottom: 20px; color: #666;">Gestión de Sugerencias, Quejas y Reclamos emitidos por los usuarios.</p>

            <?php
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                echo '<div class="alert alert-success" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:5px;">El estado del ticket ha sido actualizado correctamente.</div>';
            }
            ?>

            <div class="table-container">
                <table style="width: 100%; text-align: left; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Tipo</th>
                            <th>Asunto</th>
                            <th>Remitente (Contacto)</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_tickets && mysqli_num_rows($res_tickets) > 0): ?>
                            <?php while ($ticket = mysqli_fetch_assoc($res_tickets)): ?>
                                <?php
                                    // Asignar clase de color según el tipo de ticket
                                    $tipo = strtolower($ticket['nombre_tipo']);
                                    $badge_class = 'badge-sugerencia'; // Default
                                    if (strpos($tipo, 'queja') !== false) $badge_class = 'badge-queja';
                                    if (strpos($tipo, 'reclamo') !== false) $badge_class = 'badge-reclamo';
                                ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px 8px;"><strong><?php echo htmlspecialchars($ticket['codigo_ticket']); ?></strong></td>
                                    <td><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($ticket['nombre_tipo']); ?></span></td>
                                    <td><?php echo htmlspecialchars($ticket['asunto']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($ticket['remitente']); ?><br>
                                        <small style="color: #666;">📧 <?php echo htmlspecialchars($ticket['correo']); ?> | 📞 <?php echo htmlspecialchars($ticket['dato_contacto']); ?></small>
                                    </td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($ticket['fecha_registro'])); ?></td>
                                    <td>
                                        <?php if ($ticket['estado_ticket'] == 'Abierto'): ?>
                                            <span style="color: #dc3545; font-weight: bold;">🔴 Pendiente</span>
                                        <?php else: ?>
                                            <span style="color: #28a745; font-weight: bold;">🟢 Atendido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-action" style="background: #e2e6ea; color: #333;" onclick="toggleDetalle('det_<?php echo $ticket['id_ticket']; ?>')">👁️ Leer</button>
                                        
                                        <?php if ($ticket['estado_ticket'] == 'Abierto'): ?>
                                            <form action="p_mesa_ticket_estado.php" method="POST" style="display:inline-block; margin-left: 5px;">
                                                <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="Atendido">
                                                <button type="submit" class="btn-action btn-atender" onclick="return confirm('¿Marcar este ticket como Atendido/Resuelto?')">✓ Marcar Atendido</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <!-- Fila oculta desplegable con la descripción completa -->
                                <tr id="det_<?php echo $ticket['id_ticket']; ?>" class="row-details">
                                    <td colspan="7">
                                        <strong>Descripción del problema o mensaje:</strong>
                                        <p style="margin-top: 8px; white-space: pre-wrap; font-family: monospace; background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"><?php echo htmlspecialchars($ticket['descripcion_problema']); ?></p>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 30px; color: #777;">
                                    No hay tickets registrados en la Mesa de Ayuda en este momento.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Script simple para mostrar/ocultar el mensaje completo del ticket
        function toggleDetalle(id) {
            var el = document.getElementById(id);
            if (el.style.display === "table-row") {
                el.style.display = "none";
            } else {
                el.style.display = "table-row";
            }
        }
    </script>
</body>
</html>