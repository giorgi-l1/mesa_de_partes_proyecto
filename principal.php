<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

$id_usuario = $_SESSION["id_usuario"];
$id_tipo = $_SESSION["id_tipo"];

// ----------------------------------------------------
// 1. OBTENER DATOS DEL USUARIO (Perfil)
// ----------------------------------------------------
$nombre_usuario = "";
$documento_usuario = "";
$correo_usuario = "";
$tipo_texto = "";

if ($id_tipo == 4) {
    $query = "SELECT j.razon_social, j.ruc, u.correo 
              FROM datos_juridicos j 
              INNER JOIN usuarios u ON j.id_usuario = u.id_usuario 
              WHERE u.id_usuario = '$id_usuario'";
    $resultado = mysqli_query($cn, $query);
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $nombre_usuario = $fila['razon_social'];
        $documento_usuario = "RUC: " . $fila['ruc'];
        $correo_usuario = $fila['correo'];
        $tipo_texto = "Institución Externa";
    }
} else {
    $query = "SELECT p.nombres, p.apellido_paterno, p.apellido_materno, p.numero_documento, u.correo 
              FROM datos_personales p 
              INNER JOIN usuarios u ON p.id_usuario = u.id_usuario 
              WHERE u.id_usuario = '$id_usuario'";
    $resultado = mysqli_query($cn, $query);
    if ($fila = mysqli_fetch_assoc($resultado)) {
        $nombre_usuario = $fila['nombres'] . " " . $fila['apellido_paterno'] . " " . $fila['apellido_materno'];
        $documento_usuario = "DNI/Doc: " . $fila['numero_documento'];
        $correo_usuario = $fila['correo'];

        if ($id_tipo == 1)
            $tipo_texto = "Alumno";
        if ($id_tipo == 2)
            $tipo_texto = "Docente / Personal";
        if ($id_tipo == 3)
            $tipo_texto = "Egresado";
    }
}

// ----------------------------------------------------
// 2. OBTENER ESTADÍSTICAS REALES DE LA BD
// ----------------------------------------------------
$pendientes = 0;
$en_proceso = 0;
$atendidos = 0;

/* Buscamos todos los trámites del usuario y los agrupamos por su estado */
$query_stats = "SELECT e.nombre_estado, COUNT(t.id_tramite) as total 
                FROM tramites t 
                INNER JOIN estados_tramite e ON t.id_estado = e.id_estado 
                WHERE t.id_usuario = '$id_usuario' 
                GROUP BY e.id_estado";
$res_stats = mysqli_query($cn, $query_stats);

if ($res_stats) {
    while ($row = mysqli_fetch_assoc($res_stats)) {
        $estado = strtolower($row['nombre_estado']);
        // Clasificamos inteligentemente según el nombre del estado en tu BD
        if (strpos($estado, 'pendient') !== false || strpos($estado, 'ingresad') !== false) {
            $pendientes += $row['total'];
        } elseif (strpos($estado, 'proces') !== false || strpos($estado, 'derivad') !== false || strpos($estado, 'observad') !== false) {
            $en_proceso += $row['total'];
        } elseif (strpos($estado, 'atendid') !== false || strpos($estado, 'finalizad') !== false || strpos($estado, 'rechazad') !== false) {
            $atendidos += $row['total'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal.php" class="active">Inicio</a>

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
            <div class="dropdown">
                <a class="dropbtn">Mesa de Ayuda ▼</a>
                <div class="dropdown-content">
                    <a href="mesa_ayuda.php">Registrar Reclamo / Ticket</a>
                    <a href="detalle_queja_usuario.php">Visualizar mis Reclamos</a>
                </div>
            </div>
            <a href="cerrar_session.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-grid">

            <div class="left-column">
                <div class="panel" style="margin-bottom: 25px;">
                    <div class="profile-mini">
                        <div class="profile-photo">
                            <?php
                            $formatos = ['jpg', 'jpeg', 'png'];
                            $foto_encontrada = "";
                            foreach ($formatos as $ext) {
                                if (file_exists("fotos/usuario_" . $id_usuario . "." . $ext)) {
                                    $foto_encontrada = "fotos/usuario_" . $id_usuario . "." . $ext;
                                    break;
                                }
                            }
                            if (!empty($foto_encontrada)): ?>
                                <img src="<?php echo $foto_encontrada . '?v=' . time(); ?>"
                                    style="width:100%; height:100%; border-radius:50%; object-fit:cover;">
                            <?php else: ?>
                                &#128100;
                            <?php endif; ?>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo $nombre_usuario; ?></h3>
                            <p><?php echo $documento_usuario; ?></p>
                            <div class="badge"><?php echo $tipo_texto; ?></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2 class="panel-title">Acciones Rápidas</h2>
                    <a href="nuevo_tramite.php" class="action-btn btn-primary">+ Iniciar Nuevo Trámite</a>
                    <a href="mis_tramites.php" class="action-btn btn-secondary">Ver Estado de Trámites</a>
                    <a href="perfil.php" class="action-btn btn-secondary">Actualizar Datos</a>
                </div>
            </div>

            <div class="right-column">

                <div class="stats-grid">
                    <div class="stat-card" style="border-color: #ffc107;">
                        <h2><?php echo $pendientes; ?></h2>
                        <p>Pendientes</p>
                    </div>
                    <div class="stat-card" style="border-color: #0d6efd;">
                        <h2><?php echo $en_proceso; ?></h2>
                        <p>En Proceso</p>
                    </div>
                    <div class="stat-card" style="border-color: #198754;">
                        <h2><?php echo $atendidos; ?></h2>
                        <p>Atendidos</p>
                    </div>
                </div>

                <div class="panel">
                    <h2 class="panel-title">Mis Trámites Recientes</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Trámite</th>
                                    <th>Tipo de Documento</th>
                                    <th>Fecha Envío</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // ----------------------------------------------------
// 3. OBTENER ÚLTIMOS 5 TRÁMITES REALES DE LA BD
// ----------------------------------------------------
                                $query_recientes = "SELECT t.id_tramite, t.numero_expediente, tp.nombre_tramite, t.fecha_envio, e.nombre_estado 
                                  FROM tramites t 
                    INNER JOIN tipos_tramite tp ON t.id_tipo_tramite = tp.id_tipo_tramite 
                    INNER JOIN estados_tramite e ON t.id_estado = e.id_estado 
                    WHERE t.id_usuario = '$id_usuario' 
                    ORDER BY t.fecha_envio DESC 
                    LIMIT 5";

                                $res_recientes = mysqli_query($cn, $query_recientes);

                                if ($res_recientes && mysqli_num_rows($res_recientes) > 0) {
                                    while ($tramite = mysqli_fetch_assoc($res_recientes)) {

                                        // Darle color al badge según el estado
                                        $estado_bd = strtolower($tramite['nombre_estado']);
                                        $clase_badge = "status-pendiente"; // Por defecto
                                
                                        if (strpos($estado_bd, 'proces') !== false)
                                            $clase_badge = "status-proceso";
                                        if (strpos($estado_bd, 'atendid') !== false)
                                            $clase_badge = "status-atendido";

                                        // CORRECCIÓN AQUÍ: Usamos fecha_envio
                                        $fecha_formateada = date("d/m/Y H:i", strtotime($tramite['fecha_envio']));

                                        echo "<tr>";
                                        echo "<td><a href='ver_detalle.php?id=" . $tramite['id_tramite'] . "' style='color: var(--azul-institucional); font-weight: 700; text-decoration: none;'>" . $tramite['numero_expediente'] . "</a></td>";
                                        echo "<td>" . $tramite['nombre_tramite'] . "</td>";
                                        echo "<td>" . $fecha_formateada . "</td>";
                                        echo "<td><span class='status-badge $clase_badge'>" . $tramite['nombre_estado'] . "</span></td>";
                                        echo "<td><a href='ver_detalle.php?id=" . $tramite['id_tramite'] . "' class='btn-ver-detalle'>Ver detalle</a></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    // Si no hay trámites, mostramos un mensaje bonito
                                    echo "<tr>
                                            <td colspan='5' style='text-align: center; color: #888; padding: 30px;'>
                                                <div style='font-size: 2.5rem; margin-bottom: 10px;'>📂</div>
                                                <em>Aún no has enviado ningún trámite.</em><br>
                                                <a href='nuevo_tramite.php' style='color: var(--dorado-arena); font-weight:600; text-decoration:none;'>Inicia tu primer trámite aquí</a>
                                            </td>
                                          </tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>