<?php
session_start();
date_default_timezone_set('America/Lima');
// Asumimos que el administrador tiene una variable de sesión distinta, por ejemplo "auth_admin"


require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];

// ----------------------------------------------------
// 1. OBTENER DATOS DEL ADMINISTRADOR LOGUEADO
// ----------------------------------------------------
// El admin podría no estar atado a una oficina operativa específica, pero extraemos sus datos básicos.
$query_staff = "SELECT dp.nombres, dp.apellido_paterno, tu.nombre_tipo 
                FROM usuarios u
                INNER JOIN tipos_usuario tu ON u.id_tipo = tu.id_tipo
                LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
                WHERE u.id_usuario = '$id_usuario'
                LIMIT 1";
$res_staff = mysqli_query($cn, $query_staff);

if (!$res_staff || mysqli_num_rows($res_staff) == 0) {
    die("Error al cargar el perfil de administrador. Contacta a soporte.");
}
$staff = mysqli_fetch_assoc($res_staff);

// Consulta para llenar el modal de derivación (Todas las oficinas)
$query_todas_oficinas = "SELECT id_oficina, nombre_oficina, siglas FROM oficinas ORDER BY nombre_oficina ASC";
$res_oficinas = mysqli_query($cn, $query_todas_oficinas);
$oficinas_disponibles = [];
if ($res_oficinas) {
    while ($row = mysqli_fetch_assoc($res_oficinas)) {
        $oficinas_disponibles[] = $row;
    }
}

// --- INICIO DASHBOARD MÉTRICAS GLOBALES (ADMIN) ---
// 1. Trámites Totales en Sistema
$q_totales = "SELECT COUNT(*) as total FROM tramites";
$r_totales = mysqli_fetch_assoc(mysqli_query($cn, $q_totales));
$total_sistema_dash = $r_totales['total'];

// 2. Trámites Pendientes/En Proceso a nivel global (Estado != 4 y != 5)
$q_pendientes = "SELECT COUNT(*) as total FROM tramites WHERE id_estado NOT IN (4, 5)";
$r_pendientes = mysqli_fetch_assoc(mysqli_query($cn, $q_pendientes));
$total_pendientes_dash = $r_pendientes['total'];

// 3. Trámites Finalizados Globales
$q_finalizados = "SELECT COUNT(*) as total FROM tramites WHERE id_estado = 5";
$r_finalizados = mysqli_fetch_assoc(mysqli_query($cn, $q_finalizados));
$total_finalizados_dash = $r_finalizados['total'];
// --- FIN DASHBOARD MÉTRICAS ---

// Los administradores tienen permisos totales por defecto en su vista
$puede_finalizar = true;
$puede_derivar = true;

// ----------------------------------------------------
// 2. MENSAJES DE CONFIRMACIÓN / ERROR (via GET)
// ----------------------------------------------------
$mensaje = "";
$tipo_mensaje = "";
if (isset($_GET['ok']) && $_GET['ok'] == 'finalizado') {
    $mensaje = "El trámite fue forzado a Atendido/Finalizado correctamente.";
    $tipo_mensaje = "exito";
} elseif (isset($_GET['ok']) && $_GET['ok'] == 'derivado') {
    $mensaje = "El trámite fue derivado a la nueva área correctamente.";
    $tipo_mensaje = "exito";
} elseif (isset($_GET['ok']) && $_GET['ok'] == 'rechazado') {
    $mensaje = "El trámite fue observado/rechazado correctamente.";
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
// 4. TOTAL DE TRÁMITES (VISTA GLOBAL PARA PAGINACIÓN)
// ----------------------------------------------------
$query_total = "SELECT COUNT(*) AS total FROM tramites";
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
// 5. LISTADO DE TRÁMITES (VISTA GLOBAL)
// ----------------------------------------------------
// Se muestran todos los trámites, ordenados por los más recientes
$query_bandeja = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.descripcion_motivo, t.fecha_envio,
                          e.nombre_estado, e.id_estado,
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
                   ORDER BY t.fecha_envio DESC
                   LIMIT $por_pagina OFFSET $offset";
$res_bandeja = mysqli_query($cn, $query_bandeja);

// --- 1. COMPLETAR KPIs FALTANTES ---
// Tickets de ayuda pendientes (Corregido a string 'Abierto')
$q_tickets = "SELECT COUNT(*) as total FROM tickets_ayuda WHERE estado_ticket = 'Abierto'";
$r_tickets = mysqli_query($cn, $q_tickets);
$total_tickets = ($r_tickets && $row = mysqli_fetch_assoc($r_tickets)) ? $row['total'] : 0;

// Tiempo promedio de resolución (Trámites finalizados = id_estado 5)
// Corregido: Usamos 'movimientos_tramite' para cruzar la fecha de finalización
// Tiempo promedio de resolución (Solución al error de null)
$q_tiempo = "SELECT AVG(DATEDIFF(m.fecha_envio, t.fecha_envio)) as promedio_dias 
             FROM tramites t
             INNER JOIN movimientos_tramite m ON t.id_tramite = m.id_tramite
             WHERE t.id_estado = 5 AND m.id_estado_mov = 5";
$r_tiempo = mysqli_query($cn, $q_tiempo);
$row = ($r_tiempo) ? mysqli_fetch_assoc($r_tiempo) : null;
$tiempo_promedio = ($row && $row['promedio_dias'] !== null)
    ? round((float) $row['promedio_dias'], 1) : 0;

// --- 2. CUELLOS DE BOTELLA ---
$q_cuellos = "SELECT o.nombre_oficina, COUNT(t.id_tramite) as total_estancados
              FROM tramites t
              INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
              WHERE t.id_estado IN (1, 2)
              GROUP BY o.id_oficina
              ORDER BY total_estancados DESC
              LIMIT 5";
$res_cuellos = mysqli_query($cn, $q_cuellos);

// --- 3. DATOS PARA CHART.JS (ÚLTIMOS 7 DÍAS - CORREGIDO PARA EVITAR GRÁFICOS ROTOS) ---
$fechas_chart = [];
// Generamos los últimos 7 días exactos vía PHP inicializados en 0
for ($i = 6; $i >= 0; $i--) {
    $fecha_iterada = date('Y-m-d', strtotime("-$i days"));
    $fechas_chart[$fecha_iterada] = 0;
}

$q_grafico = "SELECT DATE(fecha_envio) as fecha, COUNT(*) as cantidad
              FROM tramites
              WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DATE(fecha_envio)";
$res_grafico = mysqli_query($cn, $q_grafico);

if ($res_grafico) {
    while ($row = mysqli_fetch_assoc($res_grafico)) {
        $fecha_db = $row['fecha'];
        if (isset($fechas_chart[$fecha_db])) {
            $fechas_chart[$fecha_db] = (int) $row['cantidad'];
        }
    }
}
// Separar en arrays para Javascript
$labels_chart = array_keys($fechas_chart);
$data_chart = array_values($fechas_chart);

// --- 4. TABLA DE RECIENTES (LIMIT 20) ---
// Corregido: Se agregaron las columnas dp.nombres, dp.apellido_paterno y dj.razon_social al SELECT
$query_bandeja = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.fecha_envio,
                          e.nombre_estado, e.id_estado,
                          o.nombre_oficina, o.siglas,
                          dp.nombres, dp.apellido_paterno,
                          dj.razon_social
                   FROM tramites t
                   INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                   INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
                   LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                   LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                   ORDER BY t.fecha_envio DESC
                   LIMIT 20";
$res_bandeja = mysqli_query($cn, $query_bandeja);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración Global | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .estado-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .estado-1 {
            background-color: #6c757d;
        }

        /* Pendiente */
        .estado-2 {
            background-color: #ffc107;
            color: #000;
        }

        /* En Revisión */
        .estado-3 {
            background-color: #17a2b8;
        }

        /* Derivado */
        .estado-4 {
            background-color: #dc3545;
        }

        /* Rechazado/Observado */
        .estado-5 {
            background-color: #28a745;
        }

        /* Finalizado */
    </style>
</head>

<body>

<?php include 'cabecera_admin.php'; ?>

    <div class="container">

        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
            <!-- Tarjetas Anteriores -->
            <div
                style="flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin: 0; color: #666; font-size: 0.8em;">TOTAL TRÁMITES</h4>
                <p style="margin: 5px 0 0 0; font-size: 1.8em; font-weight: bold;"><?php echo $total_sistema_dash; ?>
                </p>
            </div>
            <div
                style="flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin: 0; color: #666; font-size: 0.8em;">EN CURSO</h4>
                <p style="margin: 5px 0 0 0; font-size: 1.8em; font-weight: bold;"><?php echo $total_pendientes_dash; ?>
                </p>
            </div>
            <div
                style="flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin: 0; color: #666; font-size: 0.8em;">FINALIZADOS</h4>
                <p style="margin: 5px 0 0 0; font-size: 1.8em; font-weight: bold;">
                    <?php echo $total_finalizados_dash; ?>
                </p>
            </div>

            <!-- NUEVAS TARJETAS -->
            <div
                style="flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin: 0; color: #666; font-size: 0.8em;">TICKETS AYUDA</h4>
                <p style="margin: 5px 0 0 0; font-size: 1.8em; font-weight: bold; color: #dc3545;">
                    <?php echo $total_tickets; ?>
                </p>
            </div>
            <div
                style="flex: 1; min-width: 150px; background: #fff; padding: 15px; border-radius: 8px; border-left: 4px solid #17a2b8; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin: 0; color: #666; font-size: 0.8em;">TIEMPO PROM. (Días)</h4>
                <p style="margin: 5px 0 0 0; font-size: 1.8em; font-weight: bold;"><?php echo $tiempo_promedio; ?> d</p>
            </div>
        </div>
        <!-- ================= FIN DASHBOARD ================= -->
        <!-- Importar Chart.js en el <head> o aquí antes del script -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <div style="display: flex; gap: 20px; margin-bottom: 20px;">

            <!-- GRÁFICO (Evolución 7 días) -->
            <div class="panel" style="flex: 2; padding: 20px; margin:0;">
                <h3 style="margin-top:0; font-size:16px; color:#333;">Evolución de Trámites (Últimos 7 días)</h3>
                <canvas id="tramitesChart" height="100"></canvas>
            </div>

            <!-- CUELLOS DE BOTELLA -->
            <div class="panel"
                style="flex: 1; padding: 20px; margin:0; background-color: #fffafb; border: 1px solid #f5c6cb;">
                <h3 style="margin-top:0; font-size:16px; color:#721c24;">⚠️ Alerta: Cuellos de Botella</h3>
                <p style="font-size: 12px; color: #666;">Oficinas con más trámites estancados.</p>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php if ($res_cuellos && mysqli_num_rows($res_cuellos) > 0): ?>
                        <?php while ($cuello = mysqli_fetch_assoc($res_cuellos)): ?>
                            <li
                                style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                                <span
                                    style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($cuello['nombre_oficina']); ?></span>
                                <span
                                    style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                                    <?php echo $cuello['total_estancados']; ?>
                                </span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li style="font-size: 13px; color: #28a745;">No hay cuellos de botella detectados.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <script>
            const ctx = document.getElementById('tramitesChart').getContext('2d');
            const tramitesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels_chart); ?>,
                    datasets: [{
                        label: 'Trámites Ingresados',
                        data: <?php echo json_encode($data_chart); ?>,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 } // Para que no muestre decimales si los números son bajos
                        }
                    }
                }
            });
        </script>

        <div class="panel">
            <div class="panel-header-flex">
                <h2 class="panel-title panel-title-clean">Monitoreo Global de Expedientes</h2>
                <span class="pill-cargo">Superadministrador</span>
            </div>
            <p class="panel-subtitulo">Vista general de todos los documentos fluyendo por el sistema.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje-flash mensaje-<?php echo $tipo_mensaje; ?>"><?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>EXPEDIENTE</th>
                            <th>SOLICITANTE</th>
                            <th>ASUNTO</th>
                            <th>ÁREA ACTUAL</th>
                            <th>ESTADO</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_bandeja && mysqli_num_rows($res_bandeja) > 0): ?>
                            <?php while ($t = mysqli_fetch_assoc($res_bandeja)): ?>
                                <?php
                                $nombre_solicitante = !empty($t['razon_social'])
                                    ? $t['razon_social']
                                    : trim($t['nombres'] . ' ' . $t['apellido_paterno']);
                                if ($nombre_solicitante === '')
                                    $nombre_solicitante = '—';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['numero_expediente']); ?></strong><br><small
                                            style="color:#777;"><?php echo date('d/m/Y H:i', strtotime($t['fecha_envio'])); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($nombre_solicitante); ?></td>
                                    <td><?php echo htmlspecialchars($t['asunto']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($t['nombre_oficina']); ?></strong><br><small>(<?php echo htmlspecialchars($t['siglas']); ?>)</small>
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo $t['id_estado']; ?>">
                                            <?php echo htmlspecialchars($t['nombre_estado']); ?>
                                        </span>
                                    </td>
                                    
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="tabla-vacia">
                                    <div class="tabla-vacia-icono">📭</div>
                                    <em>La base de datos de trámites está vacía.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>



        </div>
    </div>



</body>

</html>