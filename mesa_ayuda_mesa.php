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
if ($pagina_actual < 1)
    $pagina_actual = 1;
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
                         dj.razon_social,
                         tr.numero_expediente -- CAMBIO: Ahora traemos el expediente
                  FROM tickets_ayuda t
                  INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                  INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
                  LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                  LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                  LEFT JOIN tramites tr ON t.id_tramite = tr.id_tramite -- CAMBIO: Unimos con tramites
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
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
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
                🏢 <?php echo htmlspecialchars($staff['nombre_oficina']); ?>
                (<?php echo htmlspecialchars($staff['siglas']); ?>)
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
            <p class="panel-subtitulo">Bandeja administrativa para leer, gestionar y atender las sugerencias, quejas o
                reclamos emitidos por los usuarios.</p>

            <?php if ($mensaje_flash): ?>
                <div class="mensaje-flash mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo htmlspecialchars($mensaje_flash); ?>
                </div>
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
                                if ($remitente === '')
                                    $remitente = 'Anónimo / Desconocido';

                                $telefono = !empty($tk['telefono']) ? $tk['telefono'] : '—';

                                $badge_color = "#6c757d";
                                if (strcasecmp($tk['tipo_ticket'], 'Reclamo') == 0)
                                    $badge_color = "#dc3545";
                                if (strcasecmp($tk['tipo_ticket'], 'Queja') == 0)
                                    $badge_color = "#ffc107";
                                if (strcasecmp($tk['tipo_ticket'], 'Sugerencia') == 0)
                                    $badge_color = "#28a745";
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tk['codigo_ticket']); ?></strong></td>
                                    <td>
                                        <span
                                            style="background-color: <?php echo $badge_color; ?>; color: white; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block;">
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
                                        <span
                                            style="color: #555; font-size: 12px;"><?php echo htmlspecialchars($tk['mensaje']); ?></span>
                                        <!-- CAMBIO: Muestra el expediente si el ticket lo tiene -->
                                        <?php if (!empty($tk['numero_expediente'])): ?>
                                            <br>
                                            <span
                                                style="background-color: #17a2b8; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; display: inline-block; margin-top: 5px;">
                                                📄 Expediente: <?php echo htmlspecialchars($tk['numero_expediente']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span
                                            style="font-size: 12px;"><?php echo htmlspecialchars($tk['fecha_creacion']); ?></span>
                                    </td>
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
                                        <!-- CAMBIO: Flexbox para alinear el botón de detalle con los de estado -->
                                        <div style="display: flex; gap: 5px; align-items: center; justify-content: flex-start;">

                                            <!-- NUEVO BOTÓN DE DETALLES -->
                                            <a href="detalle_ticket.php?id_ticket=<?php echo $tk['id_ticket']; ?>"
                                                class="btn-accion" title="Ver Detalles"
                                                style="background-color: #6c757d; color: white; padding: 6px 10px; text-decoration: none; border-radius: 4px; font-size: 14px;">
                                                👁️
                                            </a>

                                            <?php if (strcasecmp($tk['estado'], 'Abierto') == 0 || strcasecmp($tk['estado'], 'Pendiente') == 0): ?>
                                                <form action="mesa_ayuda_mesa.php" method="POST" class="form-inline"
                                                    style="margin:0;">
                                                    <input type="hidden" name="id_ticket" value="<?php echo $tk['id_ticket']; ?>">
                                                    <input type="hidden" name="accion" value="leer">
                                                    <button type="submit" class="btn-accion btn-finalizar"
                                                        style="background-color: #007bff;">Marcar Leído</button>
                                                </form>
                                            <?php elseif (strcasecmp($tk['estado'], 'Leído') == 0): ?>
                                                <form action="mesa_ayuda_mesa.php" method="POST"
                                                    onsubmit="return confirm('¿Confirmas que has revisado y atendido este ticket?');"
                                                    class="form-inline" style="margin:0;">
                                                    <input type="hidden" name="id_ticket" value="<?php echo $tk['id_ticket']; ?>">
                                                    <input type="hidden" name="accion" value="atender">
                                                    <button type="submit" class="btn-accion btn-finalizar"
                                                        style="background-color: #28a745;">Marcar Atendido</button>
                                                </form>
                                            <?php else: ?>
                                                <button disabled class="btn-accion"
                                                    style="background-color: #e0e0e0; color: #a0a0a0; cursor: not-allowed; border: none;">Listo</button>
                                            <?php endif; ?>

                                        </div>
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
                        Mostrando <?php echo ($offset + 1); ?>–<?php echo min($offset + $por_pagina, $total_tickets); ?> de
                        <?php echo $total_tickets; ?> tickets
                    </span>
                    <div class="paginacion-controles">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">&larr;
                                Anterior</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">&larr; Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $p; ?>"
                                class="pagina-btn <?php echo ($p == $pagina_actual) ? 'pagina-activa' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="mesa_ayuda_mesa.php?pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Siguiente
                                &rarr;</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">Siguiente &rarr;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div id="chatbot-bubble" onclick="toggleChat()"
        style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background-color: #007bff; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 9999; transition: transform 0.2s;">
        <span style="font-size: 30px; color: white;">🤖</span>
    </div>

    <div id="chatbot-window"
        style="position: fixed; bottom: 90px; right: 20px; width: 320px; height: 400px; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; z-index: 9999; border: 1px solid #ddd; font-family: 'Montserrat', sans-serif;">
        <!-- Encabezado -->
        <div
            style="background: #007bff; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
            <span>🤖 Asistente Virtual UNJFSC</span>
            <button onclick="toggleChat()"
                style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">✕</button>
        </div>

        <!-- Cuerpo del Chat -->
        <div id="chat-body"
            style="flex: 1; padding: 15px; overflow-y: auto; background: #f8f9fa; display: flex; flex-direction: column; gap: 10px;">
            <div
                style="background: #e9ecef; padding: 10px; border-radius: 8px; font-size: 13px; max-width: 85%; color: #333;">
                ¡Hola! Soy tu asistente virtual. ¿En qué te puedo ayudar hoy de forma rápida?
            </div>

            <!-- Opciones Rápidas -->
            <div id="chat-options" style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                <button onclick="botResponder('tramites')"
                    style="background: white; border: 1px solid #007bff; color: #007bff; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🔍
                    Consultar / Buscar Trámites</button>
                <button onclick="botResponder('ayuda')"
                    style="background: white; border: 1px solid #007bff; color: #007bff; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🎫
                    Ir a Mesa de Ayuda</button>
                <button onclick="botResponder('horario')"
                    style="background: white; border: 1px solid #007bff; color: #007bff; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🕒
                    Horario de Atención</button>
            </div>
        </div>
    </div>

</body>



</html>
<script>
function toggleChat() {
    const windowChat = document.getElementById('chatbot-window');
    const bubble = document.getElementById('chatbot-bubble');
    if (windowChat.style.display === 'none' || windowChat.style.display === '') {
        windowChat.style.display = 'flex';
        bubble.style.transform = 'scale(0.9)';
    } else {
        windowChat.style.display = 'none';
        bubble.style.transform = 'scale(1)';
    }
}

function botResponder(opcion) {
    const chatBody = document.getElementById('chat-body');
    const optionsDiv = document.getElementById('chat-options');
    
    // Ocultar opciones temporales para simular flujo
    optionsDiv.style.display = 'none';
    
    let respuestaTexto = "";
    let botonAccion = "";

    if (opcion === 'tramites') {
        respuestaTexto = "Para buscar, revisar y hacer seguimiento a los expedientes del sistema, puedes ir directamente al buscador de trámites.";
        botonAccion = `<a href="tramites/ver_tramites.php" style="display:inline-block; background:#007bff; color:white; padding:8px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-top:5px;">Ir a Búsqueda de Trámites ➡️</a>`;
    } else if (opcion === 'ayuda') {
        respuestaTexto = "Si tienes inconvenientes, quejas o reclamos con alguna solicitud, nuestra Mesa de Ayuda está disponible para atenderte.";
        botonAccion = `<a href="mesa_ayuda_mesa.php" style="display:inline-block; background:#28a745; color:white; padding:8px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-top:5px;">Ir a Mesa de Ayuda ➡️</a>`;
    } else if (opcion === 'horario') {
        respuestaTexto = "🏢 El horario de atención presencial y virtual de la Unidad de Trámite Documentario es de Lunes a Viernes de 08:00 AM a 04:00 PM.";
    }

    // Insertar respuesta del bot
    setTimeout(() => {
        chatBody.innerHTML += `
            <div style="background: #e9ecef; padding: 10px; border-radius: 8px; font-size: 13px; max-width: 85%; color: #333; margin-top: 5px;">
                ${respuestaTexto}<br>${botonAccion}
            </div>
        `;
        
        // Volver a mostrar las opciones abajo por si quiere consultar otra cosa
        optionsDiv.style.display = 'flex';
        chatBody.appendChild(optionsDiv);
        
        // Auto-scroll al fondo
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 400);
}
</script>
