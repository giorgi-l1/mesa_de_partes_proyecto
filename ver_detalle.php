<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];

// ----------------------------------------------------
// 1. VALIDAR Y OBTENER EL ID DEL TRÁMITE
// ----------------------------------------------------
$id_tramite = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_tramite <= 0) {
    header("Location: principal.php");
    exit();
}

// ----------------------------------------------------
// 2. OBTENER DATOS GENERALES DEL TRÁMITE
// (Solo puede ver el trámite el dueño del expediente)
// ----------------------------------------------------
$query_tramite = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.descripcion_motivo,
                          t.fecha_envio, t.observacion_admin,
                          tp.nombre_tramite, tp.descripcion AS descripcion_tipo,
                          e.nombre_estado, e.id_estado,
                          o.nombre_oficina, o.siglas
                   FROM tramites t
                   INNER JOIN tipos_tramite tp ON t.id_tipo_tramite = tp.id_tipo_tramite
                   INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                   INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
                   WHERE t.id_tramite = '$id_tramite' AND t.id_usuario = '$id_usuario'
                   LIMIT 1";

$res_tramite = mysqli_query($cn, $query_tramite);

if (!$res_tramite || mysqli_num_rows($res_tramite) == 0) {
    // El trámite no existe o no pertenece al usuario logueado
    header("Location: principal.php?error=notfound");
    exit();
}

$tramite = mysqli_fetch_assoc($res_tramite);

// Clase de color para el badge de estado
$estado_bd = strtolower($tramite['nombre_estado']);
$clase_badge = "status-pendiente";
if (strpos($estado_bd, 'revis') !== false || strpos($estado_bd, 'proces') !== false) {
    $clase_badge = "status-proceso";
} elseif (strpos($estado_bd, 'derivad') !== false) {
    $clase_badge = "status-derivado";
} elseif (strpos($estado_bd, 'observad') !== false || strpos($estado_bd, 'rechazad') !== false) {
    $clase_badge = "status-observado";
} elseif (strpos($estado_bd, 'atendid') !== false || strpos($estado_bd, 'finalizad') !== false) {
    $clase_badge = "status-atendido";
}

// ----------------------------------------------------
// 3. OBTENER DOCUMENTOS ADJUNTOS
// ----------------------------------------------------
$query_adjuntos = "SELECT id_documento, nombre_adjunto, nombre_archivo, ruta_archivo, enlace_externo, fecha_subida
                    FROM documentos_adjuntos
                    WHERE id_tramite = '$id_tramite'
                    ORDER BY id_documento ASC";
$res_adjuntos = mysqli_query($cn, $query_adjuntos);

// ----------------------------------------------------
// 4. OBTENER HISTORIAL DE MOVIMIENTOS
// ----------------------------------------------------
$query_movimientos = "SELECT m.numero_movimiento, m.fecha_envio, m.fecha_recepcion, m.observaciones,
                              eo.nombre_estado,
                              oo.nombre_oficina AS oficina_origen,
                              od.nombre_oficina AS oficina_destino
                       FROM movimientos_tramite m
                       INNER JOIN estados_tramite eo ON m.id_estado_mov = eo.id_estado
                       INNER JOIN oficinas oo ON m.id_oficina_origen = oo.id_oficina
                       INNER JOIN oficinas od ON m.id_oficina_destino = od.id_oficina
                       WHERE m.id_tramite = '$id_tramite'
                       ORDER BY m.numero_movimiento ASC";
$res_movimientos = mysqli_query($cn, $query_movimientos);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Trámite <?php echo htmlspecialchars($tramite['numero_expediente']); ?> | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal.php">Inicio</a>

            <div class="dropdown">
                <a class="dropbtn">Trámites ▼</a>
                <div class="dropdown-content">
                    <a href="nuevo_tramite.php">Iniciar Nuevo Trámite</a>
                    <a href="mis_tramites.php">Revisar Mis Trámites</a>
                    <a href="historial_tramites.php">Historial Completo</a>
                </div>
            </div>

            <div class="dropdown">
                <a class="dropbtn">Mi Cuenta ▼</a>
                <div class="dropdown-content">
                    <a href="perfil.php">Actualizar Datos Personales</a>
                    <a href="perfil_foto.php">Cambiar Foto de Perfil</a>
                    <a href="cambiar_password.php">Cambiar Contraseña</a>
                </div>
            </div>
            <a href="mesa_ayuda.php">Mesa de Ayuda</a>
            <a href="cerrar_session.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container-sm">

        <div class="detalle-header">
            <div>
                <a href="principal.php" class="btn-volver">&larr; Volver al panel</a>
                <h1 class="detalle-titulo">Expediente <?php echo htmlspecialchars($tramite['numero_expediente']); ?></h1>
                <p class="detalle-subtitulo"><?php echo htmlspecialchars($tramite['nombre_tramite']); ?></p>
            </div>
            <div class="detalle-header-acciones">
                <span class="status-badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($tramite['nombre_estado']); ?></span>
                <a href="exportar_pdf.php?id=<?php echo $tramite['id_tramite']; ?>" class="action-btn btn-primary btn-pdf">
                    📄 Descargar Constancia (PDF)
                </a>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 25px;">
            <h2 class="panel-title">Detalle del Trámite</h2>

            <div class="detalle-grid">
                <div class="detalle-item">
                    <span class="detalle-label">Tipo de Trámite</span>
                    <span class="detalle-valor"><?php echo htmlspecialchars($tramite['nombre_tramite']); ?></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Fecha de Envío</span>
                    <span class="detalle-valor"><?php echo date("d/m/Y H:i", strtotime($tramite['fecha_envio'])); ?></span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Oficina Actual</span>
                    <span class="detalle-valor"><?php echo htmlspecialchars($tramite['nombre_oficina']); ?> (<?php echo htmlspecialchars($tramite['siglas']); ?>)</span>
                </div>
                <div class="detalle-item">
                    <span class="detalle-label">Estado Actual</span>
                    <span class="detalle-valor"><?php echo htmlspecialchars($tramite['nombre_estado']); ?></span>
                </div>
                <div class="detalle-item full-width">
                    <span class="detalle-label">Asunto</span>
                    <span class="detalle-valor"><?php echo nl2br(htmlspecialchars($tramite['asunto'])); ?></span>
                </div>
                <div class="detalle-item full-width">
                    <span class="detalle-label">Descripción / Motivo</span>
                    <span class="detalle-valor"><?php echo nl2br(htmlspecialchars($tramite['descripcion_motivo'])); ?></span>
                </div>
                <?php if (!empty($tramite['observacion_admin'])): ?>
                <div class="detalle-item full-width">
                    <span class="detalle-label">Observación de la Administración</span>
                    <span class="detalle-valor detalle-observacion"><?php echo nl2br(htmlspecialchars($tramite['observacion_admin'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 25px;">
            <h2 class="panel-title">Documentos Adjuntos</h2>

            <?php if ($res_adjuntos && mysqli_num_rows($res_adjuntos) > 0): ?>
                <ul class="lista-adjuntos">
                    <?php while ($adj = mysqli_fetch_assoc($res_adjuntos)): ?>
                        <li class="adjunto-item">
                            <span class="adjunto-icono">📎</span>
                            <div class="adjunto-info">
                                <strong><?php echo htmlspecialchars($adj['nombre_adjunto']); ?></strong>
                                <span class="adjunto-fecha">Subido el <?php echo date("d/m/Y H:i", strtotime($adj['fecha_subida'])); ?></span>
                            </div>
                            <?php if (!empty($adj['ruta_archivo'])): ?>
                                <a href="<?php echo htmlspecialchars($adj['ruta_archivo']); ?>" target="_blank" class="adjunto-descarga">Ver archivo</a>
                            <?php elseif (!empty($adj['enlace_externo'])): ?>
                                <a href="<?php echo htmlspecialchars($adj['enlace_externo']); ?>" target="_blank" class="adjunto-descarga">Ver enlace</a>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="sin-datos">Este trámite no tiene documentos adjuntos registrados.</p>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h2 class="panel-title">Historial de Movimientos</h2>

            <?php if ($res_movimientos && mysqli_num_rows($res_movimientos) > 0): ?>
                <div class="timeline">
                    <?php while ($mov = mysqli_fetch_assoc($res_movimientos)): ?>
                        <div class="timeline-item">
                            <div class="timeline-punto"></div>
                            <div class="timeline-contenido">
                                <div class="timeline-cabecera">
                                    <strong>Movimiento N° <?php echo $mov['numero_movimiento']; ?></strong>
                                    <span class="timeline-fecha"><?php echo date("d/m/Y H:i", strtotime($mov['fecha_envio'])); ?></span>
                                </div>
                                <p class="timeline-ruta">
                                    <?php echo htmlspecialchars($mov['oficina_origen']); ?>
                                    &rarr;
                                    <?php echo htmlspecialchars($mov['oficina_destino']); ?>
                                </p>
                                <p class="timeline-estado">Estado: <?php echo htmlspecialchars($mov['nombre_estado']); ?></p>
                                <?php if (!empty($mov['observaciones'])): ?>
                                    <p class="timeline-obs"><?php echo nl2br(htmlspecialchars($mov['observaciones'])); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($mov['fecha_recepcion'])): ?>
                                    <p class="timeline-recepcion">Recepcionado el <?php echo date("d/m/Y H:i", strtotime($mov['fecha_recepcion'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="sin-datos">Aún no se han registrado movimientos para este trámite.</p>
            <?php endif; ?>
        </div>

    </div>

</body>

</html>
