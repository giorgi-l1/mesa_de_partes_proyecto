<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];

// ----------------------------------------------------
// 1. PAGINACIÓN (10 registros por página)
// ----------------------------------------------------
$por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $por_pagina;

// ----------------------------------------------------
// 2. TOTAL DE TRÁMITES DEL USUARIO (para calcular N° de páginas)
// ----------------------------------------------------
$query_total = "SELECT COUNT(*) AS total FROM tramites WHERE id_usuario = '$id_usuario'";
$res_total = mysqli_query($cn, $query_total);
$total_tramites = 0;
if ($res_total && $fila_total = mysqli_fetch_assoc($res_total)) {
    $total_tramites = intval($fila_total['total']);
}
$total_paginas = max(1, ceil($total_tramites / $por_pagina));

// Si piden una página fuera de rango, la ajustamos
if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $por_pagina;
}

// ----------------------------------------------------
// 3. OBTENER LOS TRÁMITES DE LA PÁGINA ACTUAL
//    (Asunto + Área/Oficina actual, según lo pedido)
// ----------------------------------------------------
$query_tramites = "SELECT t.id_tramite, t.numero_expediente, t.asunto, t.fecha_envio,
                          tp.nombre_tramite,
                          e.nombre_estado,
                          o.nombre_oficina, o.siglas
                   FROM tramites t
                   INNER JOIN tipos_tramite tp ON t.id_tipo_tramite = tp.id_tipo_tramite
                   INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
                   INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
                   WHERE t.id_usuario = '$id_usuario'
                   ORDER BY t.fecha_envio DESC
                   LIMIT $por_pagina OFFSET $offset";
$res_tramites = mysqli_query($cn, $query_tramites);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Trámites | UNJFSC</title>
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
                    <a href="mis_tramites.php" class="active">Revisar Mis Trámites</a>
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

    <div class="container">

        <div class="panel">
            <h2 class="panel-title">Mis Trámites</h2>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Asunto</th>
                            <th>Área</th>
                            <th>Fecha Envío</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_tramites && mysqli_num_rows($res_tramites) > 0): ?>
                            <?php while ($tramite = mysqli_fetch_assoc($res_tramites)): ?>
                                <?php
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
                                ?>
                                <tr>
                                    <td>
                                        <a href="ver_detalle.php?id=<?php echo $tramite['id_tramite']; ?>" style="color: var(--azul-institucional); font-weight: 700; text-decoration: none;">
                                            <?php echo htmlspecialchars($tramite['numero_expediente']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($tramite['asunto']); ?></td>
                                    <td><?php echo htmlspecialchars($tramite['nombre_oficina']); ?> <span style="color:#999; font-size:0.8rem;">(<?php echo htmlspecialchars($tramite['siglas']); ?>)</span></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($tramite['fecha_envio'])); ?></td>
                                    <td><span class="status-badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($tramite['nombre_estado']); ?></span></td>
                                    <td><a href="ver_detalle.php?id=<?php echo $tramite['id_tramite']; ?>" class="btn-ver-detalle">Ver detalle</a></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='6' style='text-align: center; color: #888; padding: 30px;'>
                                    <div style='font-size: 2.5rem; margin-bottom: 10px;'>📂</div>
                                    <em>Aún no has enviado ningún trámite.</em><br>
                                    <a href='nuevo_tramite.php' style='color: var(--dorado-arena); font-weight:600; text-decoration:none;'>Inicia tu primer trámite aquí</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_tramites > 0): ?>
                <div class="paginacion">
                    <span class="paginacion-info">
                        Mostrando <?php echo ($offset + 1); ?>–<?php echo min($offset + $por_pagina, $total_tramites); ?> de <?php echo $total_tramites; ?> trámites
                    </span>
                    <div class="paginacion-controles">
                        <?php if ($pagina_actual > 1): ?>
                            <a href="mis_tramites.php?pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">&larr; Anterior</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">&larr; Anterior</span>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <a href="mis_tramites.php?pagina=<?php echo $p; ?>" class="pagina-btn <?php echo ($p == $pagina_actual) ? 'pagina-activa' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>

                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="mis_tramites.php?pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Siguiente &rarr;</a>
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
