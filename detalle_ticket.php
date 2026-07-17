<?php
session_start();

// 1. CONTROL DE ACCESO EXCLUSIVO PARA MESA DE PARTES
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';

// Verificar que llegue el ID del ticket
if (!isset($_GET['id_ticket']) || empty($_GET['id_ticket'])) {
    header("Location: mesa_ayuda_mesa.php");
    exit();
}

$id_ticket = intval($_GET['id_ticket']);
$id_usuario = $_SESSION["id_usuario"];

// OBTENER DATOS DE OFICINA Y ROL DEL TRABAJADOR LOGUEADO (Para el Navbar)
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
// 2. OBTENER DETALLES COMPLETOS DEL TICKET
// ----------------------------------------------------
$query_ticket = "SELECT t.*, tp.nombre_tipo AS tipo_ticket,
                        u.correo AS usuario_email,
                        dp.nombres, dp.apellido_paterno, dp.apellido_materno, dp.tipo_documento, dp.numero_documento, dp.celular AS cel_personal,
                        dj.razon_social, dj.ruc, dj.correo_empresarial,
                        tr.numero_expediente, tr.asunto AS asunto_tramite, tr.fecha_envio AS fecha_tramite
                 FROM tickets_ayuda t
                 INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                 INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
                 LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                 LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                 LEFT JOIN tramites tr ON t.id_tramite = tr.id_tramite
                 WHERE t.id_ticket = '$id_ticket'";
$res_ticket = mysqli_query($cn, $query_ticket);

if (!$res_ticket || mysqli_num_rows($res_ticket) == 0) {
    echo "<h3>Error: El ticket solicitado no existe o fue eliminado.</h3>";
    echo "<a href='mesa_ayuda_mesa.php'>Volver a la bandeja</a>";
    exit();
}

$ticket = mysqli_fetch_assoc($res_ticket);

// Preparar variables para mostrar (Determinar si es persona natural o jurídica)
$es_empresa = !empty($ticket['razon_social']);
$remitente_nombre = $es_empresa ? $ticket['razon_social'] : trim($ticket['nombres'] . ' ' . $ticket['apellido_paterno'] . ' ' . $ticket['apellido_materno']);

// CORRECCIÓN 1: Usamos 'tipo_documento' y 'numero_documento' en lugar de 'dni'
$remitente_doc = $es_empresa ? "RUC: " . $ticket['ruc'] : $ticket['tipo_documento'] . ": " . $ticket['numero_documento'];

// CORRECCIÓN 2: Como las empresas no tienen celular en la BD, mostramos "No registrado" por defecto
$remitente_cel = $es_empresa ? "No registrado" : $ticket['cel_personal'];

$remitente_correo = $es_empresa && !empty($ticket['correo_empresarial']) ? $ticket['correo_empresarial'] : $ticket['usuario_email'];
// Colores para el estado y tipo
$badge_color_tipo = "#6c757d";
if (strcasecmp($ticket['tipo_ticket'], 'Reclamo') == 0)
    $badge_color_tipo = "#dc3545";
if (strcasecmp($ticket['tipo_ticket'], 'Queja') == 0)
    $badge_color_tipo = "#ffc107";
if (strcasecmp($ticket['tipo_ticket'], 'Sugerencia') == 0)
    $badge_color_tipo = "#28a745";

$badge_color_estado = "#dc3545"; // Abierto
if (strcasecmp($ticket['estado_ticket'], 'Leído') == 0)
    $badge_color_estado = "#007bff";
if (strcasecmp($ticket['estado_ticket'], 'Atendido') == 0)
    $badge_color_estado = "#28a745";

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Ticket | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .detalle-container {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .detalle-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            flex: 1;
            min-width: 300px;
        }

        .detalle-card h3 {
            margin-top: 0;
            color: #343a40;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .detalle-item {
            margin-bottom: 12px;
            font-size: 14px;
        }

        .detalle-item strong {
            color: #495057;
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }

        .btn-volver {
            display: inline-block;
            margin-bottom: 15px;
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }

        .btn-volver:hover {
            background: #5a6268;
        }

        .mensaje-box {
            background: white;
            border: 1px solid #ced4da;
            padding: 15px;
            border-radius: 5px;
            font-style: italic;
            color: #333;
            line-height: 1.5;
        }
    </style>
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

            <a href="mesa_ayuda_mesa.php" class="btn-volver">⬅ Volver a la Bandeja</a>

            <div class="panel-header-flex">
                <h2 class="panel-title panel-title-clean">Detalle del Ticket:
                    <?php echo htmlspecialchars($ticket['codigo_ticket']); ?></h2>
                <span class="badge" style="background-color: <?php echo $badge_color_estado; ?>;">Estado:
                    <?php echo htmlspecialchars(strtoupper($ticket['estado_ticket'])); ?></span>
            </div>

            <div class="detalle-container">
                <!-- COLUMNA 1: DATOS DEL TICKET -->
                <div class="detalle-card">
                    <h3>📑 Información del Ticket</h3>
                    <div class="detalle-item">
                        <strong>Tipo de Ticket:</strong>
                        <span class="badge"
                            style="background-color: <?php echo $badge_color_tipo; ?>;"><?php echo htmlspecialchars(strtoupper($ticket['tipo_ticket'])); ?></span>
                    </div>
                    <div class="detalle-item">
                        <strong>Fecha de Registro:</strong>
                        <?php echo htmlspecialchars($ticket['fecha_registro']); ?>
                    </div>
                    <div class="detalle-item">
                        <strong>Asunto:</strong>
                        <?php echo htmlspecialchars($ticket['asunto']); ?>
                    </div>
                    <div class="detalle-item">
                        <strong>Mensaje / Detalle del problema:</strong>
                        <div class="mensaje-box">
                            "<?php echo nl2br(htmlspecialchars($ticket['descripcion_problema'])); ?>"
                        </div>
                    </div>
                </div>

                <!-- COLUMNA 2: DATOS DEL REMITENTE -->
                <div class="detalle-card">
                    <h3>👤 Datos del Remitente (<?php echo $es_empresa ? 'Persona Jurídica' : 'Persona Natural'; ?>)
                    </h3>
                    <div class="detalle-item">
                        <strong>Nombre / Razón Social:</strong>
                        <?php echo htmlspecialchars($remitente_nombre); ?>
                    </div>
                    <div class="detalle-item">
                        <strong>Documento:</strong>
                        <?php echo htmlspecialchars($remitente_doc); ?>
                    </div>
                    <div class="detalle-item">
                        <strong>Correo de Contacto:</strong>
                        📧 <?php echo htmlspecialchars($remitente_correo); ?>
                    </div>
                    <div class="detalle-item">
                        <strong>Celular:</strong>
                        📞 <?php echo htmlspecialchars($remitente_cel ?: 'No especificado'); ?>
                    </div>
                </div>

                <!-- COLUMNA 3: EXPEDIENTE RELACIONADO (Opcional) -->
                <?php if (!empty($ticket['numero_expediente'])): ?>
                    <div class="detalle-card" style="border-left: 4px solid #17a2b8;">
                        <h3>📄 Expediente Vinculado</h3>
                        <div class="detalle-item">
                            <strong>N° Expediente:</strong>
                            <span
                                style="font-size: 16px; color: #17a2b8; font-weight: bold;"><?php echo htmlspecialchars($ticket['numero_expediente']); ?></span>
                        </div>
                        <div class="detalle-item">
                            <strong>Fecha de Trámite:</strong>
                            <?php echo htmlspecialchars($ticket['fecha_tramite']); ?>
                        </div>
                        <div class="detalle-item">
                            <strong>Asunto del Trámite original:</strong>
                            <?php echo htmlspecialchars($ticket['asunto_tramite']); ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

</body>

</html>