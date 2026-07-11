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

// ----------------------------------------------------
// 6. LISTA DE OFICINAS DISPONIBLES PARA DERIVAR
// ----------------------------------------------------
$query_oficinas = "SELECT id_oficina, nombre_oficina, siglas FROM oficinas WHERE id_oficina != '$id_oficina' ORDER BY nombre_oficina ASC";
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
                                        
                                        <a href="tramites/rechazar_tramite.php?id=<?php echo $t['id_tramite']; ?>" 
                                           onclick="return confirm('¿Estás completamente seguro de rechazar el expediente <?php echo htmlspecialchars($t['numero_expediente'], ENT_QUOTES); ?>?');" 
                                           class="btn-accion" 
                                           style="background-color: #dc3545; color: white; text-decoration: none; display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-left: 5px;">
                                            Rechazar
                                        </a>
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
                                    (<?php echo htmlspecialchars($of['siglas']); ?>)</option>
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
        </script>
    <?php endif; ?>

</body>
</html>