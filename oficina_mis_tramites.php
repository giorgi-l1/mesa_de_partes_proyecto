<?php
session_start();

if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: login_mesa.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

// 1. OBTENER DATOS DE LA OFICINA
$query_staff = "SELECT du.id_oficina, o.nombre_oficina, o.siglas, du.cargo_real
                FROM datos_oficina_usuario du
                INNER JOIN oficinas o ON du.id_oficina = o.id_oficina
                WHERE du.id_usuario = '$id_usuario' LIMIT 1";
$res_staff = mysqli_query($cn, $query_staff);

if (!$res_staff || mysqli_num_rows($res_staff) == 0) {
    die("Error: No tienes una oficina asignada.");
}
$staff = mysqli_fetch_assoc($res_staff);
$id_oficina = $staff['id_oficina'];

// 2. CAPTURAR FILTROS (Búsqueda, Fechas, Estado)
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'finalizados';
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($cn, $_GET['busqueda']) : '';
$f_desde = isset($_GET['f_desde']) ? $_GET['f_desde'] : '';
$f_hasta = isset($_GET['f_hasta']) ? $_GET['f_hasta'] : '';

// 3. CONSTRUIR CLÁUSULAS SQL SEGÚN FILTROS
$join_movimientos = "INNER JOIN movimientos_tramite m ON t.id_tramite = m.id_tramite";

if ($filtro == 'derivados') {
    // Trámites donde mi oficina fue ORIGEN en algún movimiento (independientemente de dónde estén ahora)
    $base_where = "m.id_oficina_origen = '$id_oficina'";
    $titulo_vista = "Historial de Trámites Derivados por esta Oficina";
} else {
    // Trámites donde mi oficina fue DESTINO y el estado final es 5
    $base_where = "m.id_oficina_destino = '$id_oficina' AND t.id_estado = 5";
    $titulo_vista = "Trámites Finalizados en esta Oficina";
}
$filtros_extra = "";
if (!empty($busqueda)) {
    $filtros_extra .= " AND (t.numero_expediente LIKE '%$busqueda%' OR dp.nombres LIKE '%$busqueda%' OR dp.apellido_paterno LIKE '%$busqueda%' OR dj.razon_social LIKE '%$busqueda%') ";
}
if (!empty($f_desde)) {
    $filtros_extra .= " AND DATE(t.fecha_envio) >= '$f_desde' ";
}
if (!empty($f_hasta)) {
    $filtros_extra .= " AND DATE(t.fecha_envio) <= '$f_hasta' ";
}

$where_clause = $base_where . $filtros_extra;

// 4. CONSULTA PRINCIPAL (Usada para Listar y para Exportar)
$query_base = "SELECT DISTINCT t.id_tramite, t.numero_expediente, t.asunto, t.fecha_envio,
                       e.nombre_estado, tu.nombre_tipo,
                       dp.nombres, dp.apellido_paterno, dp.apellido_materno, dj.razon_social,
                       o_actual.nombre_oficina as oficina_actual
                FROM tramites t
                $join_movimientos
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                INNER JOIN tipos_usuario tu ON u.id_tipo = tu.id_tipo
                INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                INNER JOIN oficinas o_actual ON t.id_oficina_actual = o_actual.id_oficina
                LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                WHERE $where_clause
                ORDER BY t.fecha_envio DESC";

// --- EXPORTAR A EXCEL SI SE PRESIONÓ EL BOTÓN ---
if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Reporte_Tramites_" . date('Ymd_His') . ".xls");
    
    $res_export = mysqli_query($cn, $query_base);
    echo '<meta charset="utf-8">';
    echo '<table border="1">';
    echo '<tr>
            <th style="background:#004085; color:white;">Nº EXPEDIENTE</th>
            <th style="background:#004085; color:white;">REMITENTE</th>
            <th style="background:#004085; color:white;">ASUNTO</th>
            <th style="background:#004085; color:white;">FECHA ENVIO</th>
            <th style="background:#004085; color:white;">ESTADO ACTUAL</th>
            <th style="background:#004085; color:white;">UBICACIÓN ACTUAL</th>
          </tr>';
    while ($row = mysqli_fetch_assoc($res_export)) {
        $nombre = !empty($row['razon_social']) ? $row['razon_social'] : trim($row['nombres'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno']);
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['numero_expediente']) . '</td>';
        echo '<td>' . htmlspecialchars($nombre) . '</td>';
        echo '<td>' . htmlspecialchars($row['asunto']) . '</td>';
        echo '<td>' . htmlspecialchars($row['fecha_envio']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nombre_estado']) . '</td>';
        echo '<td>' . htmlspecialchars($row['oficina_actual']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
// --- FIN EXPORTAR ---

// 5. PAGINACIÓN (40 por página)[cite: 16]
$por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

// Contar totales para paginación
$query_total = "SELECT COUNT(DISTINCT t.id_tramite) AS total 
                FROM tramites t 
                $join_movimientos 
                LEFT JOIN datos_personales dp ON t.id_usuario = dp.id_usuario
                LEFT JOIN datos_juridicos dj ON t.id_usuario = dj.id_usuario
                WHERE $where_clause";
$res_total = mysqli_query($cn, $query_total);
$total_tramites = ($res_total && $fila_total = mysqli_fetch_assoc($res_total)) ? intval($fila_total['total']) : 0;
$total_paginas = max(1, ceil($total_tramites / $por_pagina));

if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener datos para la vista actual
$query_lista = $query_base . " LIMIT $por_pagina OFFSET $offset";
$res_lista = mysqli_query($cn, $query_lista);

// 6. EXTRAER HISTORIALES PARA EL MODAL (Solo de los registros en pantalla)
$ids_tramites = [];
$datos_tabla = [];
while ($t = mysqli_fetch_assoc($res_lista)) {
    $ids_tramites[] = $t['id_tramite'];
    $datos_tabla[] = $t;
}

$historiales_json = "{}";
if (!empty($ids_tramites)) {
    $ids_str = implode(",", $ids_tramites);
    // CORRECCIÓN: Se cambió m.fecha_movimiento por m.fecha_envio y m.accion por m.observaciones
    $q_hist = "SELECT m.id_tramite, m.fecha_envio as fecha_movimiento, 
                      IFNULL(o_orig.nombre_oficina, 'Mesa de Partes') as origen, 
                      o_dest.nombre_oficina as destino, 
                      m.observaciones as accion 
               FROM movimientos_tramite m
               LEFT JOIN oficinas o_orig ON m.id_oficina_origen = o_orig.id_oficina
               LEFT JOIN oficinas o_dest ON m.id_oficina_destino = o_dest.id_oficina
               WHERE m.id_tramite IN ($ids_str) 
               ORDER BY m.fecha_envio ASC";
    $r_hist = mysqli_query($cn, $q_hist);
    $hist_array = [];
    while ($h = mysqli_fetch_assoc($r_hist)) {
        $hist_array[$h['id_tramite']][] = $h;
    }
    $historiales_json = json_encode($hist_array);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Trámites Gestionados | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Estilos para el Filtro Complejo */
        .filtros-avanzados { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .filtros-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.85em; font-weight: 600; color: #555; margin-bottom: 5px; }
        .form-group input, .form-group select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 5px; font-family: 'Montserrat', sans-serif; }
        .acciones-filtro { display: flex; gap: 10px; margin-top: 15px; }
        .btn-buscar { background: #0056b3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .btn-excel { background: #1e7e34; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: 600; }
        
        /* Badges Semánticos */
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 0.8em; font-weight: 600; text-transform: uppercase;}
        .badge-verde { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-azul { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .badge-rojo { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .badge-gris { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }

        /* Botón e Icono de Historial */
        .btn-icon { background: none; border: none; font-size: 1.2em; cursor: pointer; color: #0056b3; transition: 0.2s; }
        .btn-icon:hover { color: #004085; transform: scale(1.1); }

        /* Modal Historial */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; width: 90%; max-width: 600px; padding: 20px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); max-height: 80vh; overflow-y: auto;}
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;}
        .modal-close { cursor: pointer; font-size: 1.5em; font-weight: bold; color: #aaa; border:none; background:none;}
        .modal-close:hover { color: #333; }
        .historial-item { border-left: 3px solid #0056b3; padding-left: 15px; margin-bottom: 15px; }
        .historial-fecha { font-size: 0.85em; color: #666; font-weight: bold; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Oficinas</span></div>
        <div class="nav-links">
            <a href="principal_oficina.php">Bandeja Pendientes</a>
            <a href="oficina_mis_tramites.php" class="active">Mi Historial</a>
            <span class="nav-info-oficina">🏢 <?php echo htmlspecialchars($staff['nombre_oficina']); ?></span>
            <a href="cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="panel">
            <h2 class="panel-title"><?php echo htmlspecialchars($titulo_vista); ?></h2>

            <!-- PANEL DE FILTROS AVANZADOS -->
            <div class="filtros-avanzados">
                <form action="oficina_mis_tramites.php" method="GET">
                    <div class="filtros-grid">
                        <div class="form-group">
                            <label for="filtro">Tipo de Registro</label>
                            <select name="filtro" id="filtro">
                                <option value="finalizados" <?php echo ($filtro == 'finalizados') ? 'selected' : ''; ?>>Finalizados aquí</option>
                                <option value="derivados" <?php echo ($filtro == 'derivados') ? 'selected' : ''; ?>>Derivados a otras áreas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="busqueda">Buscar (Nº Exp / Remitente)</label>
                            <input type="text" name="busqueda" id="busqueda" placeholder="Ej: 2023-0001 o Juan" value="<?php echo htmlspecialchars($busqueda); ?>">
                        </div>
                        <div class="form-group">
                            <label for="f_desde">Desde (Fecha)</label>
                            <input type="date" name="f_desde" id="f_desde" value="<?php echo htmlspecialchars($f_desde); ?>">
                        </div>
                        <div class="form-group">
                            <label for="f_hasta">Hasta (Fecha)</label>
                            <input type="date" name="f_hasta" id="f_hasta" value="<?php echo htmlspecialchars($f_hasta); ?>">
                        </div>
                    </div>
                    <div class="acciones-filtro">
                        <button type="submit" class="btn-buscar">🔍 Aplicar Filtros</button>
                        <!-- El botón manda el formulario con el parámetro exportar=excel -->
                        <button type="submit" name="exportar" value="excel" class="btn-excel">📊 Exportar a Excel</button>
                        <a href="oficina_mis_tramites.php" style="margin-left:auto; align-self:center; color:#555; text-decoration:none;">Limpiar Filtros</a>
                    </div>
                </form>
            </div>

            <!-- TABLA DE RESULTADOS -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nº EXPEDIENTE</th>
                            <th>REMITENTE</th>
                            <th>ASUNTO</th>
                            <th>ESTADO ACTUAL</th>
                            <th>UBICACIÓN ACTUAL</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($datos_tabla) > 0): ?>
                            <?php foreach ($datos_tabla as $t): ?>
                                <?php
                                $nombre = !empty($t['razon_social']) ? $t['razon_social'] : trim($t['nombres'] . ' ' . $t['apellido_paterno'] . ' ' . $t['apellido_materno']);
                                
                                // Asignación de color según el estado
                                $estado_str = strtolower($t['nombre_estado']);
                                if (strpos($estado_str, 'finalizado') !== false || strpos($estado_str, 'atendido') !== false) {
                                    $clase_badge = 'badge-verde';
                                } elseif (strpos($estado_str, 'derivado') !== false || strpos($estado_str, 'proceso') !== false) {
                                    $clase_badge = 'badge-azul';
                                } elseif (strpos($estado_str, 'observado') !== false || strpos($estado_str, 'rechazado') !== false) {
                                    $clase_badge = 'badge-rojo';
                                } else {
                                    $clase_badge = 'badge-gris';
                                }
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($t['numero_expediente']); ?></strong><br><small><?php echo date('d/m/Y', strtotime($t['fecha_envio'])); ?></small></td>
                                    <td><?php echo htmlspecialchars($nombre); ?></td>
                                    <td><?php echo htmlspecialchars($t['asunto']); ?></td>
                                    <td><span class="badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($t['nombre_estado']); ?></span></td>
                                    <td><?php echo htmlspecialchars($t['oficina_actual']); ?></td>
                                    <td style="text-align:center;">
                                        <button class="btn-icon" title="Ver Historial" onclick="abrirHistorial(<?php echo $t['id_tramite']; ?>, '<?php echo $t['numero_expediente']; ?>')">👁️</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding: 20px;"><em>No se encontraron trámites con los filtros aplicados.</em></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <?php if ($total_tramites > 0): ?>
                <div class="paginacion">
                    <span class="paginacion-info">Mostrando <?php echo ($offset + 1); ?>–<?php echo min($offset + $por_pagina, $total_tramites); ?> de <?php echo $total_tramites; ?></span>
                    <div class="paginacion-controles">
                        <?php 
                        // Mantener los parámetros en la URL de paginación
                        $params = $_GET; 
                        unset($params['pagina'], $params['exportar']);
                        $query_string = http_build_query($params);
                        ?>
                        
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?<?php echo $query_string; ?>&pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">&larr; Ant</a>
                        <?php endif; ?>
                        
                        <?php for ($p = max(1, $pagina_actual - 2); $p <= min($total_paginas, $pagina_actual + 2); $p++): ?>
                            <a href="?<?php echo $query_string; ?>&pagina=<?php echo $p; ?>" class="pagina-btn <?php echo ($p == $pagina_actual) ? 'pagina-activa' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?<?php echo $query_string; ?>&pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Sig &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL HISTORIAL -->
    <div class="modal-overlay" id="modalHistorial">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin:0;">Historial: <span id="modalExpediente"></span></h3>
                <button class="modal-close" onclick="cerrarHistorial()">&times;</button>
            </div>
            <div id="modalBody" style="margin-top:15px;">
                <!-- Aquí se inyecta el historial mediante JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Data del historial cargada desde PHP
        const dataHistorial = <?php echo $historiales_json; ?>;

        function abrirHistorial(id_tramite, expediente) {
            document.getElementById('modalExpediente').innerText = expediente;
            const container = document.getElementById('modalBody');
            container.innerHTML = '';

            const movimientos = dataHistorial[id_tramite];

            if (movimientos && movimientos.length > 0) {
                movimientos.forEach(mov => {
                    let html = `
                        <div class="historial-item">
                            <div class="historial-fecha">📅 ${mov.fecha_movimiento}</div>
                            <div><strong>De:</strong> ${mov.origen} ➡️ <strong>A:</strong> ${mov.destino}</div>
                            <div style="font-size:0.9em; margin-top:5px; color:#555;"><em>${mov.accion}</em></div>
                        </div>
                    `;
                    container.innerHTML += html;
                });
            } else {
                container.innerHTML = '<p>No hay movimientos registrados para este trámite.</p>';
            }

            document.getElementById('modalHistorial').style.display = 'flex';
        }

        function cerrarHistorial() {
            document.getElementById('modalHistorial').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera del contenido
        document.getElementById('modalHistorial').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarHistorial();
            }
        });
    </script>
</body>
</html>