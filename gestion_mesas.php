<?php
session_start();
require 'conexion.php';

// --- 1. CONFIGURACIÓN DE PAGINACIÓN ---
$registros_por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina_actual < 1)
    $pagina_actual = 1;

$inicio = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros para paginación (Solo Mesa de Partes, id_tipo = 6)
$q_total = "SELECT COUNT(*) as total FROM usuarios WHERE id_tipo = 6";
$res_total = mysqli_query($cn, $q_total);
$row_total = mysqli_fetch_assoc($res_total);
$total_registros = $row_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// --- 2. OBTENER LOS DATOS DE LOS TRABAJADORES ---
$query = "SELECT u.id_usuario, u.correo, u.estado, 
                 dp.nombres, dp.apellido_paterno, dp.apellido_materno,
                 dper.cargo
          FROM usuarios u
          LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
          LEFT JOIN datos_personal dper ON u.id_usuario = dper.id_usuario
          WHERE u.id_tipo = 6
          ORDER BY u.id_usuario DESC
          LIMIT $inicio, $registros_por_pagina";

$resultado = mysqli_query($cn, $query);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventanillas | UNJFSC</title>
    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Tu CSS Principal -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <?php include 'cabecera_admin.php'; ?>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container">
        <div class="panel">

            <div class="panel-header-flex" style="margin-bottom: 20px;">
                <div>
                    <h2 class="panel-title panel-title-clean">Gestión de Ventanillas (Mesa de Partes)</h2>
                    <p class="panel-subtitulo">Administre al personal, oficinistas y cajeros que atienden los trámites
                        de la institución.</p>
                </div>
                <!-- Botón de ALTA -->
                <a href="crear_mesa.php" class="btn-ver-detalle" style="padding: 10px 20px; font-size: 0.9rem;">+ Nuevo
                    Trabajador</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ventanilla / Correo</th>
                            <th>Nombre del Trabajador</th>
                            <th>Cargo en la Mesa</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultado)):
                            $nombre_completo = trim($row['nombres'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno']);
                            if (empty($nombre_completo))
                                $nombre_completo = "Sin registrar";
                            ?>
                            <tr>
                                <td><?= $row['id_usuario'] ?></td>
                                <td><strong><?= htmlspecialchars($row['correo']) ?></strong></td>
                                <td><?= htmlspecialchars($nombre_completo) ?></td>
                                <td><span class="pill-cargo"><?= htmlspecialchars($row['cargo'] ?? 'No asignado') ?></span>
                                </td>
                                <td>
                                    <?php if ($row['estado'] == 1): ?>
                                        <span class="status-badge status-atendido">Activo</span>
                                    <?php else: ?>
                                        <span class="status-badge status-rechazado">Baja</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; white-space: nowrap;">
                                    <!-- Botón de DETALLES -->
                                    <a href="detalles_mesa.php?id=<?= $row['id_usuario'] ?>"
                                        class="btn-accion status-atendido"
                                        style="text-decoration: none; color: #0c5460; background-color: #d1ecf1;">Detalles</a>
                                    <!-- Botón de EDICIÓN -->
                                    <a href="editar_mesa.php?id=<?= $row['id_usuario'] ?>"
                                        class="btn-accion status-pendiente"
                                        style="text-decoration: none; color: #856404;">Editar</a>

                                    <!-- Botón de BAJA LÓGICA -->
                                    <?php if ($row['estado'] == 1): ?>
                                        <a href="cambiar_estado_mesa.php?id=<?= $row['id_usuario'] ?>&est=0"
                                            class="btn-accion status-rechazado" style="text-decoration: none;"
                                            onclick="return confirm('¿Retirar a este trabajador de la ventanilla?');">Dar de
                                            Baja</a>
                                    <?php else: ?>
                                        <a href="cambiar_estado_mesa.php?id=<?= $row['id_usuario'] ?>&est=1"
                                            class="btn-accion status-atendido" style="text-decoration: none;"
                                            onclick="return confirm('¿Reasignar trabajador a la ventanilla?');">Reactivar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="paginacion">
                <div class="paginacion-info">
                    Mostrando página <?= $pagina_actual ?> de <?= $total_paginas ?>
                </div>
                <div class="paginacion-controles">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="gestion_mesas.php?pagina=<?= $i ?>"
                            class="pagina-btn <?= ($pagina_actual == $i) ? 'pagina-activa' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>

        </div>
    </div>
</body>

</html>