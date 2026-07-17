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
                    <a href="configuracion_alertas.php">Notificaciones</a>
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
    <div id="chatbot-bubble" onclick="toggleChat()"
        style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background-color: #791518; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 9999; transition: transform 0.2s;">
        <span style="font-size: 30px; color: white;">🤖</span>
    </div>

    <div id="chatbot-window"
        style="position: fixed; bottom: 90px; right: 20px; width: 320px; height: 400px; background: white; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; z-index: 9999; border: 1px solid #ddd; font-family: 'Montserrat', sans-serif;">
        <!-- Encabezado -->
        <div
            style="background: #791518; color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
            <span>🤖 Asistente Virtual UNJFSC</span>
            <button onclick="toggleChat()"
                style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">✕</button>
        </div>

        <!-- Cuerpo del Chat -->
        <div id="chat-body"
            style="flex: 1; padding: 15px; overflow-y: auto; background: #f8f9fa; display: flex; flex-direction: column; gap: 10px;">
            <div
                style="background: #e9ecef; padding: 10px; border-radius: 8px; font-size: 13px; max-width: 85%; color: #333;">
                ¡Hola! Soy tu asistente de la Mesa de Partes. ¿Qué deseas gestionar hoy?
            </div>

            <!-- Opciones Rápidas para el Usuario -->
            <div id="chat-options" style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                <button onclick="botResponder('nuevo')"
                    style="background: white; border: 1px solid #791518; color: #791518; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🚀
                    Iniciar un Nuevo Trámite</button>
                <button onclick="botResponder('estado')"
                    style="background: white; border: 1px solid #791518; color: #791518; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🔍
                    Ver Estado de Mis Trámites</button>
                <button onclick="botResponder('reclamo')"
                    style="background: white; border: 1px solid #791518; color: #791518; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; text-align: left; font-weight: 500; transition: 0.2s;">🎫
                    Registrar Reclamo / Ticket</button>
            </div>
        </div>
    </div>
</body>

</html>
<script>
function toggleChat() {
    const windowChat = document.getElementById('chatbot-window');
    const bubble = document.getElementById('chatbot-bubble');
    if (windowChat.style.display === 'none' || windowChat.style.display === '') {
        windowChat.style.display = 'flex';
        bubble.style.transform = 'scale(0.9)';
    } else {
        windowChat.style.display = 'none';
        bubble.style.transform = 'scale(1)';
    }
}

function botResponder(opcion) {
    const chatBody = document.getElementById('chat-body');
    const optionsDiv = document.getElementById('chat-options');
    
    optionsDiv.style.display = 'none';
    
    let respuestaTexto = "";
    let botonAccion = "";

    if (opcion === 'nuevo') {
        respuestaTexto = "Puedes registrar una nueva solicitud o adjuntar tus documentos del formato FUT directamente desde el módulo de registro.";
        botonAccion = `<a href="nuevo_tramite.php" style="display:inline-block; background:#791518; color:white; padding:8px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-top:5px;">Iniciar Trámite ➡️</a>`;
    } else if (opcion === 'estado') {
        respuestaTexto = "Para revisar en qué oficina se encuentra tu expediente, los documentos adjuntos y sus observaciones, ingresa a tu bandeja.";
        botonAccion = `<a href="mis_tramites.php" style="display:inline-block; background:#6c757d; color:white; padding:8px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-top:5px;">Mis Trámites ➡️</a>`;
    } else if (opcion === 'reclamo') {
        respuestaTexto = "Si tienes inconvenientes con el uso de la plataforma o alguna queja sobre tu atención, puedes abrir un ticket de soporte.";
        botonAccion = `<a href="mesa_ayuda.php" style="display:inline-block; background:#ffc107; color:black; padding:8px 12px; border-radius:5px; text-decoration:none; font-size:12px; font-weight:bold; margin-top:5px;">Registrar Ticket ➡️</a>`;
    }

    setTimeout(() => {
        chatBody.innerHTML += `
            <div style="background: #e9ecef; padding: 10px; border-radius: 8px; font-size: 13px; max-width: 85%; color: #333; margin-top: 5px;">
                ${respuestaTexto}<br>${botonAccion}
            </div>
        `;
        
        optionsDiv.style.display = 'flex';
        chatBody.appendChild(optionsDiv);
        chatBody.scrollTop = chatBody.scrollHeight;
    }, 400);
}
</script>