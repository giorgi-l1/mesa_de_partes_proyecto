<?php
require 'conexion.php';

$mensaje_error = "";
$tramite_encontrado = false;
$movimientos = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $anio = mysqli_real_escape_string($cn, $_POST['anio']);
    $numero = mysqli_real_escape_string($cn, $_POST['numero']);
    $clave = mysqli_real_escape_string($cn, $_POST['clave']);

    $numero_formateado = str_pad($numero, 6, "0", STR_PAD_LEFT);
    $expediente_completo = "EXP-" . $anio . "-" . $numero_formateado;

    // 1. Buscamos el trámite principal
    $query = "SELECT t.*, tp.nombre_tramite, e.nombre_estado 
              FROM tramites t 
              INNER JOIN tipos_tramite tp ON t.id_tipo_tramite = tp.id_tipo_tramite 
              INNER JOIN estados_tramite e ON t.id_estado = e.id_estado 
              INNER JOIN usuarios u ON t.id_usuario = u.id_usuario 
              WHERE t.numero_expediente = '$expediente_completo' 
              AND u.password = '$clave'";

    $resultado = mysqli_query($cn, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $tramite = mysqli_fetch_assoc($resultado);
        $tramite_encontrado = true;

        $estado_bd = strtolower($tramite['nombre_estado']);
        $clase_badge = "status-pendiente";
        if (strpos($estado_bd, 'revisi') !== false || strpos($estado_bd, 'derivad') !== false) {
            $clase_badge = "status-proceso";
        } elseif (strpos($estado_bd, 'rechazad') !== false || strpos($estado_bd, 'observad') !== false) {
            $clase_badge = "status-rechazado";
        } elseif (strpos($estado_bd, 'atendid') !== false || strpos($estado_bd, 'finalizad') !== false) {
            $clase_badge = "status-atendido";
        }

        // 2. Buscamos el historial de movimientos
        $id_tramite = $tramite['id_tramite'];
        $query_mov = "SELECT m.*, o_ori.nombre_oficina as origen, o_dest.nombre_oficina as destino, e.nombre_estado 
                      FROM movimientos_tramite m 
                      LEFT JOIN oficinas o_ori ON m.id_oficina_origen = o_ori.id_oficina 
                      LEFT JOIN oficinas o_dest ON m.id_oficina_destino = o_dest.id_oficina 
                      INNER JOIN estados_tramite e ON m.id_estado_mov = e.id_estado 
                      WHERE m.id_tramite = '$id_tramite' 
                      ORDER BY m.numero_movimiento ASC, m.fecha_envio ASC";
        $res_mov = mysqli_query($cn, $query_mov);
        if ($res_mov) {
            while ($row = mysqli_fetch_assoc($res_mov)) {
                $movimientos[] = $row;
            }
        }
    } else {
        $mensaje_error = "No se encontró ningún trámite que coincida con el expediente <strong>$expediente_completo</strong> y la contraseña ingresada.";
    }
} else {
    header("Location: consulta.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Consulta | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">

    <style>
        /* Corrección de color de texto para asegurar que se vea */
        body,
        .panel,
        .detalle-label,
        .detalle-valor,
        .timeline-content p,
        h2,
        h3,
        h4 {
            color: #2c3e50 !important;
        }

        /* Estilos para la línea de tiempo (Diagrama de flujo) */
        .timeline-container {
            margin-top: 30px;
            padding-left: 20px;
            border-left: 3px solid #003366;
            /* Azul oscuro */
            position: relative;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 20px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        /* El puntito de la línea de tiempo */
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -28px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: var(--dorado-arena, #c2a649);
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #003366;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .timeline-estado {
            font-weight: 600;
            color: #003366 !important;
            font-size: 1.05rem;
        }

        .timeline-fecha {
            font-size: 0.85rem;
            color: #666 !important;
            background: #f0f4f8;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .timeline-oficinas {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .timeline-oficinas span {
            font-weight: 600;
            color: #444 !important;
        }

        .timeline-obs {
            font-size: 0.9rem;
            background: #f9f9f9;
            padding: 10px;
            border-left: 3px solid #ccc;
            border-radius: 0 4px 4px 0;
            color: #555 !important;
        }

        /* Botón de descarga largo */
        .btn-descargar-largo {
            display: block;
            width: 100%;
            text-align: center;
            background-color: #b30000;
            /* Rojo PDF */
            color: white !important;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 40px;
            transition: background 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-descargar-largo:hover {
            background-color: #800000;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="consulta.php">Nueva Consulta</a>
            <a href="index.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container-sm">
        <div class="panel">

            <?php if (!$tramite_encontrado): ?>
                <div class="alert alert-error"
                    style="background: #f8d7da; color: #721c24 !important; border: 1px solid #f5c6cb;">
                    <?php echo $mensaje_error; ?>
                </div>
                <a href="consulta.php" class="btn-volver" style="color: #003366 !important;">← Intentar nuevamente</a>
            <?php else: ?>

                <div class="detalle-header">
                    <div>
                        <a href="consulta.php" class="btn-volver" style="color: #003366 !important;">← Volver a
                            consultas</a>
                        <h2 class="detalle-titulo">Expediente: <?php echo $tramite['numero_expediente']; ?></h2>
                        <span class="status-badge <?php echo $clase_badge; ?>"
                            style="font-size: 0.9rem; margin-top: 10px; display: inline-block;">
                            ESTADO ACTUAL: <?php echo strtoupper($tramite['nombre_estado']); ?>
                        </span>
                    </div>
                </div>

                <div class="detalle-grid" style="margin-top: 20px;">
                    <div class="detalle-item full-width">
                        <span class="detalle-label">Tipo de Trámite:</span>
                        <span class="detalle-valor"><strong><?php echo $tramite['nombre_tramite']; ?></strong></span>
                    </div>
                    <div class="detalle-item full-width">
                        <span class="detalle-label">Asunto:</span>
                        <span class="detalle-valor"><?php echo htmlspecialchars($tramite['asunto']); ?></span>
                    </div>
                </div>

                <!-- DIAGRAMA DE FLUJO / LÍNEA DE TIEMPO -->
                <h3 style="margin-top: 30px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Historial de Movimientos
                </h3>

                <?php if (count($movimientos) > 0): ?>
                    <div class="timeline-container">
                        <?php foreach ($movimientos as $mov): ?>
                            <div class="timeline-item">
                                <div class="timeline-header">
                                    <div class="timeline-estado">
                                        <?php
                                        // Personalizar el texto según el estado para que sea más lógico
                                        if ($mov['numero_movimiento'] == 1 && $mov['id_estado_mov'] == 1) {
                                            echo "ENVIADO (Mesa de Partes)";
                                        } else {
                                            echo strtoupper($mov['nombre_estado']);
                                        }
                                        ?>
                                    </div>
                                    <div class="timeline-fecha">
                                        <?php echo date("d/m/Y - h:i A", strtotime($mov['fecha_envio'])); ?>
                                    </div>
                                </div>

                                <div class="timeline-oficinas">
                                    De: <span><?php echo $mov['origen'] ?? 'Usuario Web'; ?></span>
                                    ➔ Para: <span><?php echo $mov['destino']; ?></span>
                                </div>

                                <?php if (!empty($mov['observaciones'])): ?>
                                    <div class="timeline-obs">
                                        <strong>Obs:</strong> <?php echo nl2br(htmlspecialchars($mov['observaciones'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No se registran movimientos para este expediente.</p>
                <?php endif; ?>

                <!-- BOTÓN LARGO PARA DESCARGAR PDF -->
                <!-- Cambia 'exportar_cargo.php' por el nombre real de tu archivo generador de PDF -->
                <a href="exportar_pdf.php?id=<?php echo $tramite['id_tramite']; ?>" class="action-btn btn-primary btn-pdf">
                    📥 DESCARGAR CARGO DE MOVIMIENTOS (PDF)
                </a>

            <?php endif; ?>

        </div>
    </div>

</body>

</html>