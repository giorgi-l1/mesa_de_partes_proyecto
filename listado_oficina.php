<?php
session_start();
require 'conexion.php'; // Asegúrate de que la ruta sea correcta

// --- CONFIGURACIÓN DE PAGINACIÓN (40 registros por página) ---
$por_pagina = 40;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// --- TOTAL DE OFICINAS ---
$query_total = "SELECT COUNT(*) as total FROM oficinas";
$res_total = mysqli_query($cn, $query_total);
$total_oficinas = 0;
if ($res_total && $fila = mysqli_fetch_assoc($res_total)) {
    $total_oficinas = intval($fila['total']);
}
$total_paginas = max(1, ceil($total_oficinas / $por_pagina));

// --- LISTADO DE OFICINAS ---
$query_oficinas = "SELECT id_oficina, nombre_oficina, siglas, estado 
                   FROM oficinas 
                   ORDER BY nombre_oficina ASC 
                   LIMIT $por_pagina OFFSET $offset";
$res_oficinas = mysqli_query($cn, $query_oficinas);

// --- MENSAJES DEL SISTEMA ---
$mensaje = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'creado') $mensaje = "Oficina creada correctamente.";
    if ($_GET['msg'] == 'editado') $mensaje = "Oficina actualizada correctamente.";
    if ($_GET['msg'] == 'estado') $mensaje = "Estado de la oficina modificado (Eliminación lógica).";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Oficinas | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .btn-crear { background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; margin-bottom: 15px; }
        .btn-editar { background-color: #ffc107; color: black; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .btn-detalle { background-color: #17a2b8; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .btn-inactivar { background-color: #dc3545; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .btn-activar { background-color: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; }
        .badge-activo { background-color: #d4edda; color: #155724; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; border: 1px solid #c3e6cb; }
        .badge-inactivo { background-color: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; border: 1px solid #f5c6cb; }
        .acciones-flex { display: flex; gap: 5px; justify-content: center; }
        .paginacion { margin-top: 20px; text-align: center; }
        .paginacion a { padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; color: #007bff; border-radius: 4px; }
        .paginacion a.active { background-color: #007bff; color: white; border-color: #007bff; }
    </style>
</head>
<body>

    <!-- INCLUIR LA CABECERA MODULAR -->
    <?php include 'cabecera_admin.php'; ?>

    <div class="container">
        <div class="panel">
            <div class="panel-header-flex">
                <h2 class="panel-title">Gestión de Oficinas (CRUD)</h2>
                <a href="crear_oficina.php" class="btn-crear">+ Nueva Oficina</a>
            </div>
            
            <?php if ($mensaje): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #c3e6cb;">
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NOMBRE DE OFICINA</th>
                            <th>SIGLAS</th>
                            
                            <th>ESTADO</th>
                            <th style="text-align: center;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_oficinas && mysqli_num_rows($res_oficinas) > 0): ?>
                            <?php while ($ofi = mysqli_fetch_assoc($res_oficinas)): ?>
                                <tr style="<?php echo ($ofi['estado'] == 0) ? 'opacity: 0.6; background-color: #f9f9f9;' : ''; ?>">
                                    <td><?php echo $ofi['id_oficina']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ofi['nombre_oficina']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ofi['siglas']); ?></td>
                                    
                                    <td>
                                        <?php if ($ofi['estado'] == 1): ?>
                                            <span class="badge-activo">Activo</span>
                                        <?php else: ?>
                                            <span class="badge-inactivo">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="acciones-flex">
                                        <a href="detalle_oficina.php?id=<?php echo $ofi['id_oficina']; ?>" class="btn-detalle">Detalles</a>
                                        <a href="editar_oficina.php?id=<?php echo $ofi['id_oficina']; ?>" class="btn-editar">Editar</a>
                                        
                                        <!-- ELIMINACIÓN LÓGICA -->
                                        <?php if ($ofi['estado'] == 1): ?>
                                            <a href="eliminar_oficina.php?id=<?php echo $ofi['id_oficina']; ?>&estado=0" class="btn-inactivar" onclick="return confirm('¿Seguro que deseas inactivar esta oficina?');">Inactivar</a>
                                        <?php else: ?>
                                            <a href="eliminar_oficina.php?id=<?php echo $ofi['id_oficina']; ?>&estado=1" class="btn-activar" onclick="return confirm('¿Seguro que deseas reactivar esta oficina?');">Reactivar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No hay oficinas registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="Listado_Oficina.php?pagina=<?php echo $i; ?>" class="<?php echo ($i == $pagina_actual) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>