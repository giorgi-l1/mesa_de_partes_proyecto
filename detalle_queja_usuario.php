<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

// Obtener los tickets del usuario logueado, ordenados por los más recientes
$query_tickets = "SELECT t.codigo_ticket, tp.nombre_tipo, t.asunto, t.descripcion_problema, t.fecha_registro, t.estado_ticket 
                  FROM tickets_ayuda t
                  INNER JOIN tipos_ticket tp ON t.id_tipo_ticket = tp.id_tipo_ticket
                  WHERE t.id_usuario = '$id_usuario'
                  ORDER BY t.fecha_registro DESC";

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

        </div>
    </div>
</body>

</html>