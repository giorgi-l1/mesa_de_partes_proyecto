<?php
session_start();

// Validación de sesión original
if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

/* =========================================
   LÓGICA DE PAGINACIÓN (40 por página)
========================================= */
$registros_por_pagina = 40;

// Obtener la página actual por GET, por defecto es 1
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}

// Calcular desde dónde empezar a traer registros (OFFSET)
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Contar el total de registros del usuario para saber el total de páginas
$query_total = "SELECT COUNT(*) as total FROM tickets_ayuda WHERE id_usuario = '$id_usuario'";
$result_total = mysqli_query($cn, $query_total);
$row_total = mysqli_fetch_assoc($result_total);
$total_registros = $row_total['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

/* =========================================
   CONSULTA DE TICKETS CON LIMIT
========================================= */
$query_tickets = "SELECT t.codigo_ticket, tp.nombre_tipo, t.asunto, t.descripcion_problema, t.fecha_registro, t.estado_ticket 
                  FROM tickets_ayuda t
                  INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
                  WHERE t.id_usuario = '$id_usuario'
                  ORDER BY t.fecha_registro DESC
                  LIMIT $offset, $registros_por_pagina";

$result_tickets = mysqli_query($cn, $query_tickets);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reclamos y Tickets | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Estilos adicionales específicos para los estados de los tickets */
        .badge-abierto {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-leido {
            background-color: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-atendido {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .ticket-tipo {
            font-weight: bold;
            color: #555;
        }
    </style>
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
                    <a href="configuracion_alertas.php">Notificaciones</a>
                </div>
            </div>

            <!-- Nuevo Menú de Mesa de Ayuda -->
            <div class="dropdown">
                <a class="dropbtn active">Mesa de Ayuda ▼</a>
                <div class="dropdown-content">
                    <a href="mesa_ayuda.php">Registrar Reclamo / Ticket</a>
                    <a href="detalle_queja_usuario.php" class="active">Visualizar mis Reclamos</a>
                </div>
            </div>

            <a href="cerrar_session.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="panel">
            <h2 class="panel-title">Mis Reclamos y Tickets de Soporte</h2>
            <p style="margin-bottom: 20px; color: #666;">Aquí puedes hacer seguimiento al estado de todas tus quejas,
                reclamos, sugerencias o solicitudes de soporte técnico emitidas.</p>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>TIPO</th>
                            <th>ASUNTO</th>
                            <th>FECHA DE ENVÍO</th>
                            <th>ESTADO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_tickets && mysqli_num_rows($result_tickets) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result_tickets)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['codigo_ticket']); ?></strong></td>
                                    <td><span
                                            class="ticket-tipo"><?php echo htmlspecialchars(strtoupper($row['nombre_tipo'])); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['asunto']); ?></strong><br>
                                        <span
                                            style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($row['descripcion_problema']); ?></span>
                                    </td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($row['fecha_registro'])); ?></td>
                                    <td>
                                        <?php
                                        // Asignar la clase CSS dependiendo del estado actual del ticket
                                        $estado = $row['estado_ticket'];
                                        if (strcasecmp($estado, 'Abierto') == 0 || strcasecmp($estado, 'Pendiente') == 0) {
                                            echo '<span class="badge-abierto">⏳ Abierto</span>';
                                        } elseif (strcasecmp($estado, 'Leído') == 0) {
                                            echo '<span class="badge-leido">👁️ En Revisión (Leído)</span>';
                                        } else {
                                            echo '<span class="badge-atendido">✅ Atendido</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 30px; color: #888;">
                                    <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                                    <em>No has registrado ningún reclamo o ticket de soporte.</em><br>
                                    <a href="mesa_ayuda.php"
                                        style="color: var(--dorado-arena); font-weight: bold; text-decoration: none;">Registrar
                                        uno nuevo aquí</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- CONTROLES DE PAGINACIÓN -->
            <?php if ($total_paginas > 1): ?>
                <div class="paginacion">
                    <div class="paginacion-info">
                        Mostrando página <strong><?php echo $pagina_actual; ?></strong> de <strong><?php echo $total_paginas; ?></strong> 
                        (Total: <?php echo $total_registros; ?> registros)
                    </div>
                    <div class="paginacion-controles">
                        <!-- Botón Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?pagina=<?php echo $pagina_actual - 1; ?>" class="pagina-btn">Anterior</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">Anterior</span>
                        <?php endif; ?>

                        <!-- Botones Numéricos -->
                        <?php
                        // Logica para no llenar la pantalla de botones si hay muchas páginas
                        $inicio_bucle = max(1, $pagina_actual - 2);
                        $fin_bucle = min($total_paginas, $pagina_actual + 2);

                        if ($inicio_bucle > 1) {
                            echo '<a href="?pagina=1" class="pagina-btn">1</a>';
                            if ($inicio_bucle > 2) {
                                echo '<span class="pagina-btn pagina-disabled">...</span>';
                            }
                        }

                        for ($i = $inicio_bucle; $i <= $fin_bucle; $i++) {
                            if ($i == $pagina_actual) {
                                echo '<span class="pagina-btn pagina-activa">' . $i . '</span>';
                            } else {
                                echo '<a href="?pagina=' . $i . '" class="pagina-btn">' . $i . '</a>';
                            }
                        }

                        if ($fin_bucle < $total_paginas) {
                            if ($fin_bucle < $total_paginas - 1) {
                                echo '<span class="pagina-btn pagina-disabled">...</span>';
                            }
                            echo '<a href="?pagina=' . $total_paginas . '" class="pagina-btn">' . $total_paginas . '</a>';
                        }
                        ?>

                        <!-- Botón Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina_actual + 1; ?>" class="pagina-btn">Siguiente</a>
                        <?php else: ?>
                            <span class="pagina-btn pagina-disabled">Siguiente</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>

</html>