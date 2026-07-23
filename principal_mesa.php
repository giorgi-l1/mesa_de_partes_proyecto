<?php
session_start();

if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];

// ----------------------------------------------------
// 1. OBTENER DATOS DE OFICINA Y ROL DEL TRABAJADOR LOGUEADO
// ----------------------------------------------------
$query_staff = "SELECT du.id_oficina, du.id_rol_oficina, du.cargo_real,
                       r.nombre_rol_generico, r.puede_finalizar, r.puede_derivar,
                       o.nombre_oficina, o.siglas
                FROM datos_oficina_usuario du
                INNER JOIN roles_oficina r ON du.id_rol_oficina = r.id_rol_oficina
                INNER JOIN oficinas o ON du.id_oficina = o.id_oficina
                WHERE du.id_usuario = '$id_usuario'
                LIMIT 1";
$res_staff = mysqli_query($cn, $query_staff);

if (!$res_staff || mysqli_num_rows($res_staff) == 0) {
    die("Tu cuenta de personal no tiene una oficina asignada. Contacta al administrador del sistema.");
}

// Consulta para llenar el modal de derivación
$query_todas_oficinas = "SELECT id_oficina, nombre_oficina FROM oficinas ORDER BY nombre_oficina ASC";
$lista_oficinas = mysqli_query($cn, $query_todas_oficinas);

$staff = mysqli_fetch_assoc($res_staff);
$id_oficina = $staff['id_oficina'];
$puede_finalizar = $staff['puede_finalizar'] == 1;
$puede_derivar = $staff['puede_derivar'] == 1;

// ----------------------------------------------------
// 2. MENSAJES DE CONFIRMACIÓN / ERROR (via GET)
// ----------------------------------------------------
$mensaje = "";
$tipo_mensaje = "";
if (isset($_GET['ok']) && $_GET['ok'] == 'finalizado') {
    $mensaje = "El trámite fue marcado como Atendido/Finalizado correctamente.";
    $tipo_mensaje = "exito";
} elseif (isset($_GET['ok']) && $_GET['ok'] == 'derivado') {
    $mensaje = "El trámite fue derivado a la nueva área correctamente.";
    $tipo_mensaje = "exito";
} elseif (isset($_GET['ok']) && $_GET['ok'] == 'rechazado') {
    $mensaje = "El trámite fue rechazado y retirado de la bandeja correctamente.";
    $tipo_mensaje = "exito";
} elseif (isset($_GET['error'])) {
    $mensaje = "Ocurrió un problema al procesar la acción. Intenta nuevamente.";
    $tipo_mensaje = "error";
}

// ----------------------------------------------------
// 3. PAGINACIÓN (40 registros por página)
// ----------------------------------------------------
$por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $por_pagina;

// ----------------------------------------------------
// 4. TOTAL DE TRÁMITES (Se excluyen estados 5 y 4)
// ----------------------------------------------------
$query_total = "SELECT COUNT(*) AS total FROM tramites WHERE id_oficina_actual = '$id_oficina' AND id_estado != 5 AND id_estado != 4";
$res_total = mysqli_query($cn, $query_total);
$total_tramites = 0;
if ($res_total && $fila_total = mysqli_fetch_assoc($res_total)) {
    $total_tramites = intval($fila_total['total']);
}
$total_paginas = max(1, ceil($total_tramites / $por_pagina));
if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $por_pagina;
}

// ----------------------------------------------------
// 5. LISTADO DE TRÁMITES (Se excluyen estados 5 y 4)
// ----------------------------------------------------
$query_bandeja = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.descripcion_motivo, t.fecha_envio,
                          e.nombre_estado,
                          tu.nombre_tipo,
                          dp.nombres, dp.apellido_paterno, dp.apellido_materno,
                          dj.razon_social,
                          o.nombre_oficina, o.siglas
                   FROM tramites t
                   INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                   INNER JOIN tipos_usuario tu ON u.id_tipo = tu.id_tipo
                   INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                   INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
                   LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                   LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                   WHERE t.id_oficina_actual = '$id_oficina' AND t.id_estado != 5 AND t.id_estado != 4
                   ORDER BY t.fecha_envio ASC
                   LIMIT $por_pagina OFFSET $offset";
$res_bandeja = mysqli_query($cn, $query_bandeja);

// 6. LISTA DE OFICINAS DISPONIBLES PARA DERIVAR (SOLO ACTIVAS)
$query_oficinas = "SELECT id_oficina, nombre_oficina, siglas 
                   FROM oficinas 
                   WHERE id_oficina != '$id_oficina' AND estado = 1 
                   ORDER BY nombre_oficina ASC";
$res_oficinas = mysqli_query($cn, $query_oficinas);
$oficinas_disponibles = [];
if ($res_oficinas) {
    while ($row = mysqli_fetch_assoc($res_oficinas)) {
        $oficinas_disponibles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Gestión y Derivación | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal_mesa.php" class="active">Bandeja de Trámites</a>

            <div class="dropdown">
                <a class="dropbtn">Gestión ▼</a>
                <div class="dropdown-content">
                    <a href="tramites/ver_tramites.php">Búsqueda de Trámites</a>
                    <a href="reporte/reporte_fecha.php">Reportes por Fecha</a>
                    <a href="mesa_ayuda_mesa.php">Mesa de Ayuda</a>
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
                <h2 class="panel-title panel-title-clean">
                    Bandeja de <?php echo htmlspecialchars($staff['nombre_oficina']); ?>
                </h2>
                <span class="pill-cargo"><?php echo htmlspecialchars($staff['cargo_real']); ?></span>
            </div>
            <p class="panel-subtitulo">Documentos derivados actualmente a tu oficina, pendientes de gestión.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje-flash mensaje-<?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>IDTRAMITE</th>
                            <th>NOMBRE</th>
                            <th>TIPODEUSUARIO</th>
                            <th>ASUNTO</th>
                            <th>DESCRIPCION</th>
                            <th>AREA</th>
                            <th>PDF</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_bandeja && mysqli_num_rows($res_bandeja) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($res_bandeja)): ?>
                                <?php
                                $nombre_solicitante = !empty($t['razon_social'])
                                    ? $t['razon_social']
                                    : trim($t['nombres'] . ' ' . $t['apellido_paterno'] . ' ' . $t['apellido_materno']);
                                if ($nombre_solicitante === '') {
                                    $nombre_solicitante = '—';
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['numero_expediente']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($nombre_solicitante); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($t['asunto']); ?></td>
                                    <td class="col-descripcion"><?php echo htmlspecialchars($t['descripcion_motivo']); ?></td>
                                    <td><?php echo htmlspecialchars($t['nombre_oficina']); ?></td>
                                    <td>
                                        <a href="mesa_exportar_pdf.php?id=<?php echo $t['id_tramite']; ?>" target="_blank"
                                            class="btn-icono-pdf" title="Ver constancia en PDF">📄</a>
                                    </td>
                                    <td class="col-acciones">
                                        <?php if ($puede_finalizar): ?>
                                            <form action="oficina_finalizar_tramite.php" method="POST"
                                                onsubmit="return confirm('¿Confirmas que el trámite <?php echo htmlspecialchars($t['numero_expediente']); ?> ha concluido su gestión?');"
                                                class="form-inline">
                                                <input type="hidden" name="id_tramite" value="<?php echo $t['id_tramite']; ?>">
                                                <button type="submit" class="btn-accion btn-finalizar">Finalizar</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($puede_derivar): ?>
                                            <button type="button" class="btn-accion btn-derivar"
                                                onclick="abrirModalDerivar('<?php echo $t['id_tramite']; ?>', '<?php echo htmlspecialchars($t['numero_expediente'], ENT_QUOTES); ?>')">
                                                Enviar a otra área
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="btn-accion"
                                            style="background-color: #dc3545; color: white; text-decoration: none; display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-left: 5px; border: none; cursor: pointer;"
                                            onclick="abrirModalRechazar('<?php echo $t['id_tramite']; ?>', '<?php echo htmlspecialchars($t['numero_expediente'], ENT_QUOTES); ?>')">
                                            Rechazar
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="tabla-vacia">
                                    <div class="tabla-vacia-icono">📭</div>
                                    <em>No hay trámites pendientes en la bandeja de tu oficina.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_tramites > 0): ?>
                <div class="paginacion">
                    <span class="paginacion-info">
                        Mostrando <?php echo ($offset + 1); ?>–<?php echo min($offset + $por_pagina, $total_tramites); ?> de
                        <?php echo $total_tramites; ?> trámites
                    </span>
                    <div class="paginacion-controles">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="principal_mesa.php?pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">&larr;
                                Anterior</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">&larr; Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <a href="principal_mesa.php?pagina=<?php echo $p; ?>"
                                class="pagina-btn <?php echo ($p == $pagina_actual) ? 'pagina-activa' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="principal_mesa.php?pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Siguiente
                                &rarr;</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">Siguiente &rarr;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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




    </div>

    <?php if ($puede_derivar): ?>
        <div id="modalDerivar" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <h3>Enviar trámite a otra área</h3>
                <p class="modal-expediente">Expediente: <strong id="modalExpedienteTexto"></strong></p>

                <form action="mesa_derivar_tramite.php" method="POST">
                    <input type="hidden" name="id_tramite" id="modalIdTramite" value="">

                    <div class="form-group-modal">
                        <label for="id_oficina_destino">Área de destino</label>
                        <select name="id_oficina_destino" id="id_oficina_destino" required>
                            <option value="" disabled selected>-- Selecciona un área --</option>
                            <?php foreach ($oficinas_disponibles as $of): ?>
                                <option value="<?php echo $of['id_oficina']; ?>">
                                    <?php echo htmlspecialchars($of['nombre_oficina']); ?>
                                    (<?php echo htmlspecialchars($of['siglas']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-modal">
                        <label for="observaciones">Observación (opcional)</label>
                        <textarea name="observaciones" id="observaciones" rows="3"
                            placeholder="Motivo de la derivación..."></textarea>
                    </div>

                    <div class="modal-acciones">
                        <button type="button" class="btn-modal-cancelar" onclick="cerrarModalDerivar()">Cancelar</button>
                        <button type="submit" class="btn-modal-confirmar">Confirmar Derivación</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Modal para Rechazar Trámite -->
        <div id="modalRechazar" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <h3 style="color: #dc3545;">Rechazar / Observar Trámite</h3>
                <p class="modal-expediente">Expediente: <strong id="modalExpedienteRechazoTexto"></strong></p>

                <form action="tramites/rechazar_tramite.php" method="POST">
                    <!-- Pasamos el ID por POST oculto igual que en el otro modal -->
                    <input type="hidden" name="id" id="modalIdTramiteRechazo" value="">

                    <div class="form-group-modal">
                        <label for="observaciones_rechazo">Motivo del rechazo / Observación (Obligatorio)</label>
                        <textarea name="observaciones" id="observaciones_rechazo" rows="3"
                            placeholder="Escribe el motivo exacto por el que se rechaza para notificar al usuario..."
                            required></textarea>
                    </div>

                    <div class="modal-acciones">
                        <button type="button" class="btn-modal-cancelar" onclick="cerrarModalRechazar()">Cancelar</button>
                        <!-- Botón rojo para mantener el estilo de peligro/alerta -->
                        <button type="submit" class="btn-modal-confirmar"
                            style="background-color: #dc3545; border-color: #dc3545;">Confirmar Rechazo</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            function abrirModalDerivar(idTramite, numeroExpediente) {
                document.getElementById('modalIdTramite').value = idTramite;
                document.getElementById('modalExpedienteTexto').textContent = numeroExpediente;
                document.getElementById('modalDerivar').style.display = 'flex';
            }
            function cerrarModalDerivar() {
                document.getElementById('modalDerivar').style.display = 'none';
            }
            document.getElementById('modalDerivar').addEventListener('click', function (e) {
                if (e.target === this) {
                    cerrarModalDerivar();
                }
            });


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
            // Funciones para el Modal de Rechazo
            function abrirModalRechazar(idTramite, numeroExpediente) {
                document.getElementById('modalIdTramiteRechazo').value = idTramite;
                document.getElementById('modalExpedienteRechazoTexto').textContent = numeroExpediente;
                // Limpiamos el textarea por si quedó texto de un intento anterior
                document.getElementById('observaciones_rechazo').value = '';
                document.getElementById('modalRechazar').style.display = 'flex';
            }

            function cerrarModalRechazar() {
                document.getElementById('modalRechazar').style.display = 'none';
            }

            // Cerrar al hacer clic fuera de la caja del modal de rechazo
            document.getElementById('modalRechazar').addEventListener('click', function (e) {
                if (e.target === this) {
                    cerrarModalRechazar();
                }
            });
        </script>
    <?php endif; ?>



</body>

</html>