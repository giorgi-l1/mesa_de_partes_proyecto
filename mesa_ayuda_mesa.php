<?php
session_start();

// 1. CONTROL DE ACCESO EXCLUSIVO PARA MESA DE PARTES
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];

// OBTENER DATOS DE OFICINA Y ROL DEL TRABAJADOR LOGUEADO
$query_staff = "SELECT du.id_oficina, du.id_rol_oficina, du.cargo_real,
                       r.nombre_rol_generico, o.nombre_oficina, o.siglas
                FROM datos_oficina_usuario du
                INNER JOIN roles_oficina r ON du.id_rol_oficina = r.id_rol_oficina
                INNER JOIN oficinas o ON du.id_oficina = o.id_oficina
                WHERE du.id_usuario = '$id_usuario'
                LIMIT 1";
$res_staff = mysqli_query($cn, $query_staff);
$staff = mysqli_fetch_assoc($res_staff);

// ----------------------------------------------------
// 2. ACCIÓN: CAMBIAR ESTADO DEL TICKET (Leído o Atendido)
// ----------------------------------------------------
$mensaje_flash = "";
$tipo_mensaje = "";

if (isset($_POST['accion']) && isset($_POST['id_ticket'])) {
    $id_ticket = intval($_POST['id_ticket']);
    
    if ($_POST['accion'] == 'leer') {
        $query_update = "UPDATE tickets_ayuda SET estado_ticket = 'Leído' WHERE id_ticket = '$id_ticket'";
        if (mysqli_query($cn, $query_update)) {
            $mensaje_flash = "El ticket fue marcado como Leído correctamente.";
            $tipo_mensaje = "exito";
        } else {
            $mensaje_flash = "Ocurrió un problema al actualizar el ticket.";
            $tipo_mensaje = "error";
        }
    } elseif ($_POST['accion'] == 'atender') {
        $query_update = "UPDATE tickets_ayuda SET estado_ticket = 'Atendido' WHERE id_ticket = '$id_ticket'";
        if (mysqli_query($cn, $query_update)) {
            $mensaje_flash = "El ticket fue marcado como Atendido correctamente.";
            $tipo_mensaje = "exito";
        } else {
            $mensaje_flash = "Ocurrió un problema al actualizar el ticket.";
            $tipo_mensaje = "error";
        }
    }
}

// ----------------------------------------------------
// 3. PAGINACIÓN DE TICKETS
// ----------------------------------------------------
$por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

$query_total = "SELECT COUNT(*) AS total FROM tickets_ayuda";
$res_total = mysqli_query($cn, $query_total);
$total_tickets = 0;
if ($res_total && $fila_total = mysqli_fetch_assoc($res_total)) {
    $total_tickets = intval($fila_total['total']);
}
$total_paginas = max(1, ceil($total_tickets / $por_pagina));

// ----------------------------------------------------
// 4. LISTADO DE TICKETS
// ----------------------------------------------------
$query_tickets = "SELECT t.id_ticket, t.codigo_ticket, tp.nombre_tipo AS tipo_ticket, t.asunto, 
                         t.descripcion_problema AS mensaje, t.fecha_registro AS fecha_creacion, t.estado_ticket AS estado,
                         u.correo AS usuario_email,
                         dp.nombres, dp.apellido_paterno, dp.apellido_materno, dp.celular AS telefono,
                         dj.razon_social
                  FROM tickets_ayuda t
                  INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                  INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
                  LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                  LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                  ORDER BY t.estado_ticket ASC, t.fecha_registro DESC
                  LIMIT $por_pagina OFFSET $offset";
$res_tickets = mysqli_query($cn, $query_tickets);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Ayuda - Administración | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal_mesa.php">Bandeja de Trámites</a>

            <div class="dropdown">
                <a class="dropbtn">Gestión ▼</a>
                <div class="dropdown-content">
                    <a href="tramites/ver_tramites.php">Búsqueda de Trámites</a>
                    <a href="reporte/reporte_fecha.php">Reportes por Fecha</a>
                    <a href="mesa_ayuda_mesa.php" class="active">Mesa de Ayuda</a>
                </div>
            </div>

            <span class="nav-info-oficina">
                🏢 <?php echo htmlspecialchars($staff['nombre_oficina']); ?> (<?php echo htmlspecialchars($staff['siglas']); ?>)
                &nbsp;·&nbsp; <?php echo htmlspecialchars($staff['nombre_rol_generico']); ?>
            </span>

            <a href="cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="panel">
            
            <div class="panel-header-flex">
                <h2 class="panel-title panel-title-clean">Bandeja de Mesa de Ayuda</h2>
                <span class="pill-cargo" style="background-color: #007bff;">Tickets de Soporte</span>
            </div>
            <p class="panel-subtitulo">Bandeja administrativa para leer, gestionar y atender las sugerencias, quejas o reclamos emitidos por los usuarios.</p>

            <?php if ($mensaje_flash): ?>
                <div class="mensaje-flash mensaje-<?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje_flash); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>TIPO</th>
                            <th>REMITENTE</th>
                            <th>CONTACTO</th>
                            <th>ASUNTO / DETALLE</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_tickets && mysqli_num_rows($res_tickets) > 0): ?>
                            <?php while ($tk = mysqli_fetch_assoc($res_tickets)): ?>
                                <?php
                                $remitente = !empty($tk['razon_social'])
                                    ? $tk['razon_social']
                                    : trim($tk['nombres'] . ' ' . $tk['apellido_paterno'] . ' ' . $tk['apellido_materno']);
                                if ($remitente === '') $remitente = 'Anónimo / Desconocido';

                                $telefono = !empty($tk['telefono']) ? $tk['telefono'] : '—';

                                $badge_color = "#6c757d";
                                if (strcasecmp($tk['tipo_ticket'], 'Reclamo') == 0) $badge_color = "#dc3545";
                                if (strcasecmp($tk['tipo_ticket'], 'Queja') == 0) $badge_color = "#ffc107";
                                if (strcasecmp($tk['tipo_ticket'], 'Sugerencia') == 0) $badge_color = "#28a745";
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tk['codigo_ticket']); ?></strong></td>
                                    <td>
                                        <span style="background-color: <?php echo $badge_color; ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block;">
                                            <?php echo htmlspecialchars(strtoupper($tk['tipo_ticket'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($remitente); ?></td>
                                    <td>
                                        <div style="font-size: 12px;">
                                            📧 <?php echo htmlspecialchars($tk['usuario_email']); ?><br>
                                            📞 <?php echo htmlspecialchars($telefono); ?>
                                        </div>
                                    </td>
                                    <td class="col-descripcion">
                                        <strong><?php echo htmlspecialchars($tk['asunto']); ?></strong><br>
                                        <span style="color: #555; font-size: 12px;"><?php echo htmlspecialchars($tk['mensaje']); ?></span>
                                    </td>
                                    <td><span style="font-size: 12px;"><?php echo htmlspecialchars($tk['fecha_creacion']); ?></span></td>
                                    <td>
                                        <?php if (strcasecmp($tk['estado'], 'Abierto') == 0 || strcasecmp($tk['estado'], 'Pendiente') == 0): ?>
                                            <span style="color: #dc3545; font-weight: bold; font-size: 12px;">⏳ Abierto</span>
                                        <?php elseif (strcasecmp($tk['estado'], 'Leído') == 0): ?>
                                            <span style="color: #007bff; font-weight: bold; font-size: 12px;">👁️ Leído</span>
                                        <?php else: ?>
                                            <span style="color: #28a745; font-weight: bold; font-size: 12px;">✅ Atendido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-acciones">
                                        <?php if (strcasecmp($tk['estado'], 'Abierto') == 0 || strcasecmp($tk['estado'], 'Pendiente') == 0): ?>
                                            <form action="mesa_ayuda_mesa.php" method="POST" class="form-inline">
                                                <input type="hidden" name="id_ticket" value="<?php echo $tk['id_ticket']; ?>">
                                                <input type="hidden" name="accion" value="leer">
                                                <button type="submit" class="btn-accion btn-finalizar" style="background-color: #007bff;">Marcar Leído</button>
                                            </form>
                                        <?php elseif (strcasecmp($tk['estado'], 'Leído') == 0): ?>
                                            <form action="mesa_ayuda_mesa.php" method="POST" onsubmit="return confirm('¿Confirmas que has revisado y atendido este ticket?');" class="form-inline">
                                                <input type="hidden" name="id_ticket" value="<?php echo $tk['id_ticket']; ?>">
                                                <input type="hidden" name="accion" value="atender">
                                                <button type="submit" class="btn-accion btn-finalizar" style="background-color: #28a745;">Marcar Atendido</button>
                                            </form>
                                        <?php else: ?>
                                            <button disabled class="btn-accion" style="background-color: #e0e0e0; color: #a0a0a0; cursor: not-allowed; border: none;">Listo</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="tabla-vacia">
                                    <div class="tabla-vacia-icono">📬</div>
                                    <em>No hay tickets registrados en la mesa de ayuda.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_tickets > 0): ?>
                <div class="paginacion">
                    <span class="paginacion-info">
                        Mostrando <?php echo ($offset + 1); ?>–<?php echo min($offset + $por_pagina, $total_tickets); ?> de <?php echo $total_tickets; ?> tickets
                    </span>
                    <div class="paginacion-controles">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">&larr; Anterior</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">&larr; Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $p; ?>" class="pagina-btn <?php echo ($p == $pagina_actual) ? 'pagina-activa' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Siguiente &rarr;</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">Siguiente &rarr;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>