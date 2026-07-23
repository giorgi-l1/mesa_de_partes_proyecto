<?php
session_start();
require 'conexion.php';

// Obtener todos los tipos de trámite
$query = "SELECT * FROM tipos_tramite ORDER BY id_tipo_tramite ASC";
$resultado = mysqli_query($cn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Trámites | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <?php include 'cabecera_admin.php'; ?>
    <div class="container">
        
        <!-- Formulario para Agregar Nuevo Trámite -->
        <div class="panel" style="margin-bottom: 20px;">
            <h2 class="panel-title panel-title-clean">Agregar Nuevo Tipo de Trámite</h2>
            <form action="procesar_tramite.php" method="POST" style="display: flex; gap: 15px; margin-top: 15px; align-items: center;">
                <input type="text" name="nombre_tramite" placeholder="Ej. Constancia de Egresado 2026" required style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="submit" class="btn-accion status-atendido" style="border: none; cursor: pointer; padding: 10px 20px;">+ Agregar Trámite</button>
            </form>
        </div>

        <!-- Tabla de Trámites Existentes -->
        <div class="panel">
            <h2 class="panel-title panel-title-clean">Tipos de Trámite Existentes</h2>
            <div class="table-container" style="margin-top: 15px;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Trámite</th>
                            <th>Estado</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <td><?= $row['id_tipo_tramite'] ?></td>
                            <td><strong><?= htmlspecialchars($row['nombre_tramite']) ?></strong></td>
                            <td>
                                <?php if ($row['estado'] == 1): ?>
                                    <span class="status-badge status-atendido">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-rechazado">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="editar_tramite.php?id=<?= $row['id_tipo_tramite'] ?>" class="btn-ver-detalle">Editar Nombre</a>
                                
                                <?php if ($row['estado'] == 1): ?>
                                    <a href="procesar_tramite.php?accion=estado&id=<?= $row['id_tipo_tramite'] ?>&est=0" class="btn-accion status-rechazado" style="text-decoration: none;" onclick="return confirm('¿Desactivar este trámite? Los usuarios ya no podrán seleccionarlo.');">Desactivar</a>
                                <?php else: ?>
                                    <a href="procesar_tramite.php?accion=estado&id=<?= $row['id_tipo_tramite'] ?>&est=1" class="btn-accion status-atendido" style="text-decoration: none;">Activar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>