<?php
session_start();
require 'conexion.php'; // Ajusta la ruta si es necesario

// --- 1. CONFIGURACIÓN DE PAGINACIÓN ---
$registros_por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$inicio = ($pagina_actual - 1) * $registros_por_pagina;

// Contar total de registros para paginación (Tipos 1 al 5)
$q_total = "SELECT COUNT(*) as total FROM usuarios WHERE id_tipo IN (1, 2, 3, 4, 5)";
$res_total = mysqli_query($cn, $q_total);
$row_total = mysqli_fetch_assoc($res_total);
$total_registros = $row_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// --- 2. OBTENER LOS DATOS (LÍMITE 40) ---
$query = "SELECT u.id_usuario, u.correo, u.estado, t.nombre_tipo, 
                 dp.nombres, dp.apellido_paterno, dp.apellido_materno, dp.numero_documento
          FROM usuarios u
          INNER JOIN tipos_usuario t ON u.id_tipo = t.id_tipo
          LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
          WHERE u.id_tipo IN (1, 2, 3, 4, 5)
          ORDER BY u.id_usuario DESC
          LIMIT $inicio, $registros_por_pagina";

$resultado = mysqli_query($cn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | UNJFSC</title>
    <!-- Fuentes Google -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <h2 class="panel-title panel-title-clean">Gestión de Usuarios Generales</h2>
                    <p class="panel-subtitulo">Visualización y control de accesos de ciudadanos, alumnos y personal (Solo lectura y bajas).</p>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>DNI / Doc</th>
                            <th>Nombres y Apellidos</th>
                            <th>Correo de Acceso</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultado)): 
                            $nombre_completo = trim($row['nombres'] . ' ' . $row['apellido_paterno'] . ' ' . $row['apellido_materno']);
                            $documento = $row['numero_documento'] ? $row['numero_documento'] : 'S/N';
                        ?>
                        <tr>
                            <td><?= $row['id_usuario'] ?></td>
                            <td><?= htmlspecialchars($documento) ?></td>
                            <td><?= htmlspecialchars($nombre_completo) ?></td>
                            <td><?= htmlspecialchars($row['correo']) ?></td>
                            <td><span class="pill-cargo"><?= htmlspecialchars($row['nombre_tipo']) ?></span></td>
                            <td>
                                <?php if ($row['estado'] == 1): ?>
                                    <span class="status-badge status-atendido">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-rechazado">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <!-- Ver Detalles -->
                                <a href="detalles_usuario.php?id=<?= $row['id_usuario'] ?>" class="btn-ver-detalle">Ver Detalles</a>
                                
                                <!-- Dar de Baja / Reactivar -->
                                <?php if ($row['estado'] == 1): ?>
                                    <a href="cambiar_estado_usuario.php?id=<?= $row['id_usuario'] ?>&est=0" class="btn-accion status-rechazado" style="text-decoration: none;" onclick="return confirm('¿Seguro que deseas dar de baja a este usuario?');">Dar de Baja</a>
                                <?php else: ?>
                                    <a href="cambiar_estado_usuario.php?id=<?= $row['id_usuario'] ?>&est=1" class="btn-accion status-atendido" style="text-decoration: none;" onclick="return confirm('¿Reactivar el acceso a este usuario?');">Reactivar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación con estilo dashboard -->
            <div class="paginacion">
                <div class="paginacion-info">
                    Mostrando página <?= $pagina_actual ?> de <?= $total_paginas ?>
                </div>
                <div class="paginacion-controles">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="gestion_usuarios.php?pagina=<?= $i ?>" class="pagina-btn <?= ($pagina_actual == $i) ? 'pagina-activa' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>