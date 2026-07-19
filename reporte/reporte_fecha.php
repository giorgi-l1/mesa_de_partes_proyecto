<?php
session_start();

// 1. Verificamos si es Mesa de Partes
$es_mesa = (isset($_SESSION["auth_mesa"]) && $_SESSION["auth_mesa"] == "1");

// 2. Verificamos si es una Oficina (Ajusta "id_oficina" por la variable de sesión exacta que uses en tu login de oficinas)
$es_oficina = isset($_SESSION["id_oficina"]);

// 3. Si no es NINGUNO de los dos, lo expulsamos
if (!$es_mesa && !$es_oficina) {
    // Lo mandamos al index principal o login general
    header("Location: ../index.php");
    exit();
}

include("../conexion.php");
$fechaInicio = "";
$fechaFin = "";
$where = "";

// Paginación por defecto y captura de variables
$limite_seleccionado = isset($_GET['limite']) ? $_GET['limite'] : '40';
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;

if (isset($_GET["buscar"]) || isset($_GET["pagina"])) {
    $fechaInicio = isset($_GET["fecha_inicio"]) ? $_GET["fecha_inicio"] : "";
    $fechaFin = isset($_GET["fecha_fin"]) ? $_GET["fecha_fin"] : "";

    if ($fechaInicio != "" && $fechaFin != "") {
        $where = "
        WHERE DATE(t.fecha_envio)
        BETWEEN '$fechaInicio'
        AND '$fechaFin'
        ";
    }
}

// 1. Contar el total de registros para la paginación
$sql_count = "
SELECT COUNT(*) as total 
FROM tramites t
INNER JOIN usuarios u ON t.id_usuario=u.id_usuario
INNER JOIN tipos_tramite tt ON t.id_tipo_tramite=tt.id_tipo_tramite
INNER JOIN oficinas o ON t.id_oficina_actual=o.id_oficina
INNER JOIN estados_tramite e ON t.id_estado=e.id_estado
$where
";
$resultado_count = mysqli_query($cn, $sql_count);
$total_registros = mysqli_fetch_assoc($resultado_count)['total'];

// 2. Calcular límites y páginas
$limit_sql = "";
$total_paginas = 1;

if ($limite_seleccionado !== 'todos') {
    $registros_por_pagina = intval($limite_seleccionado);
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    $limit_sql = " LIMIT $offset, $registros_por_pagina";
}

// 3. Consulta final aplicando el LIMIT si corresponde
$sql = "
SELECT
t.numero_expediente, u.correo, tt.nombre_tramite, t.asunto, o.nombre_oficina, e.nombre_estado, t.fecha_envio
FROM tramites t
INNER JOIN usuarios u ON t.id_usuario=u.id_usuario
INNER JOIN tipos_tramite tt ON t.id_tipo_tramite=tt.id_tipo_tramite
INNER JOIN oficinas o ON t.id_oficina_actual=o.id_oficina
INNER JOIN estados_tramite e ON t.id_estado=e.id_estado
$where
ORDER BY t.fecha_envio DESC
$limit_sql
";

$resultado = mysqli_query($cn, $sql);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>

        Reporte por Fechas

    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="../css/dashboard.css">

    <style>
        .filtros {

            display: grid;

            grid-template-columns: 1fr 1fr 1fr auto;

            gap: 20px;

            margin-top: 20px;

            margin-bottom: 25px;

        }

        .form-control {

            width: 100%;

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 8px;

            font-size: 14px;

        }

        .btn {

            padding: 10px 20px;

            border: none;

            border-radius: 8px;

            cursor: pointer;

            color: white;

            font-weight: bold;

            transition: .3s;

        }

        .btn:hover {

            opacity: .9;

        }

        .btn-buscar {

            background: #7b1e3d;

        }

        .btn-imprimir {

            background: #198754;

        }

        .tabla {

            width: 100%;

            border-collapse: collapse;

        }

        .tabla th {

            background: #7b1e3d;

            color: white;

            padding: 12px;

        }

        .tabla td {

            padding: 10px;

            border-bottom: 1px solid #ddd;

        }

        .estado {

            display: inline-block;

            width: 150px;

            padding: 8px;

            border-radius: 20px;

            text-align: center;

            font-size: 12px;

            font-weight: bold;

            color: white;

        }

        @media print {

            .navbar,
            .filtros,
            .btn-imprimir {
                display: none !important;
            }

            body,
            .panel,
            .container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                background: white;
            }

            body::before {
                display: none;
            }
        }
    </style>

</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="../principal_mesa.php">Bandeja de Trámites</a>

            <div class="dropdown">
                <a class="dropbtn active">Gestión ▼</a>
                <div class="dropdown-content">
                    <a href="../tramites/ver_tramites.php">Búsqueda de Trámites</a>
                    <a href="reporte_fecha.php" class="active">Reportes por Fecha</a>
                    <a href="../mesa_ayuda_mesa.php">Mesa de Ayuda</a>
                </div>
            </div>

            <a href="../cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>
    <div class="container">

        <div class="panel">

            <h2 class="panel-title">

                Reporte por Fechas

            </h2>

            <p style="margin-bottom:20px;">

                Consulte los trámites registrados dentro de un rango de fechas.

            </p>

            <form method="GET">
                <div class="filtros">
                    <div>
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?php echo $fechaInicio; ?>"
                            required>
                    </div>
                    <div>
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?php echo $fechaFin; ?>"
                            required>
                    </div>
                    <div>
                        <label>Mostrar</label>
                        <select name="limite" class="form-control">
                            <option value="todos" <?php if ($limite_seleccionado == 'todos')
                                echo 'selected'; ?>>Todos los
                                registros</option>
                            <option value="20" <?php if ($limite_seleccionado == '20')
                                echo 'selected'; ?>>20 por página
                            </option>
                            <option value="40" <?php if ($limite_seleccionado == '40')
                                echo 'selected'; ?>>40 por página
                            </option>
                            <option value="100" <?php if ($limite_seleccionado == '100')
                                echo 'selected'; ?>>100 por
                                página</option>
                        </select>
                    </div>
                    <div style="display:flex;align-items:end;">
                        <button type="submit" name="buscar" class="btn btn-buscar">Buscar</button>
                    </div>
                </div>
            </form> <!-- ⚠️ AQUÍ CERRAMOS EL FORMULARIO PARA QUE EL EDITOR NO FALLE -->



            <table class="tabla">

                <thead>

                    <tr>

                        <th>Expediente</th>

                        <th>Correo</th>

                        <th>Tipo Trámite</th>

                        <th>Asunto</th>

                        <th>Área</th>

                        <th>Estado</th>

                        <th>Fecha</th>

                    </tr>

                </thead>

                <tbody>

                    <?php

                    if (mysqli_num_rows($resultado) > 0) {

                        while ($fila = mysqli_fetch_assoc($resultado)) {

                            $color = "#ffc107";

                            switch ($fila["nombre_estado"]) {

                                case "Pendiente":
                                    $color = "#ffc107";
                                    break;

                                case "En Revisión":
                                    $color = "#0d6efd";
                                    break;

                                case "Derivado":
                                    $color = "#198754";
                                    break;

                                case "Observado/Rechazado":
                                    $color = "#dc3545";
                                    break;

                                case "Atendido/Finalizado":
                                    $color = "#6f42c1";
                                    break;

                            }

                            ?>

                            <tr>

                                <td>

                                    <?php echo htmlspecialchars($fila["numero_expediente"]); ?>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($fila["correo"]); ?>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($fila["nombre_tramite"]); ?>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($fila["asunto"]); ?>

                                </td>

                                <td>

                                    <?php echo htmlspecialchars($fila["nombre_oficina"]); ?>

                                </td>

                                <td>

                                    <span class="estado" style="background:<?php echo $color; ?>;">

                                        <?php echo htmlspecialchars($fila["nombre_estado"]); ?>

                                    </span>

                                </td>

                                <td>

                                    <?php

                                    echo date(

                                        "d/m/Y H:i",

                                        strtotime($fila["fecha_envio"])

                                    );

                                    ?>

                                </td>

                            </tr>

                            <?php

                        }

                    } else {

                        ?>

                        <tr>

                            <td colspan="7" style="text-align:center;padding:30px;color:#777;">

                                No existen trámites registrados para el rango de fechas seleccionado.

                            </td>

                        </tr>

                        <?php

                    }

                    ?>

                </tbody>

            </table>


            <!-- BLOQUE DE PAGINACIÓN VISIBLE SIEMPRE -->
            <div
                style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 10px 15px; border-radius: 8px;">
                <span style="font-size: 14px; color: #555;">
                    <?php if ($limite_seleccionado === 'todos'): ?>
                        Mostrando <strong>todos</strong> los registros (<strong><?php echo $total_registros; ?></strong> en
                        total)
                    <?php else: ?>
                        Mostrando página <strong><?php echo $pagina_actual; ?></strong> de
                        <strong><?php echo $total_paginas > 0 ? $total_paginas : 1; ?></strong>
                        (<strong><?php echo $total_registros; ?></strong> registros en total)
                    <?php endif; ?>
                </span>

                <div style="display: flex; gap: 10px;">
                    <?php if ($limite_seleccionado !== 'todos'): ?>

                        <!-- Botón Anterior -->
                        <?php if ($pagina_actual > 1): ?>
                            <a href="?fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>&limite=<?php echo $limite_seleccionado; ?>&pagina=<?php echo $pagina_actual - 1; ?>&buscar="
                                class="btn" style="background:#6c757d; font-size: 13px; text-decoration:none;">Anterior</a>
                        <?php else: ?>
                            <span class="btn"
                                style="background:#e9ecef; color:#adb5bd; font-size: 13px; cursor:not-allowed;">Anterior</span>
                        <?php endif; ?>

                        <!-- Botón Siguiente -->
                        <?php if ($pagina_actual < $total_paginas): ?>
                            <a href="?fecha_inicio=<?php echo $fechaInicio; ?>&fecha_fin=<?php echo $fechaFin; ?>&limite=<?php echo $limite_seleccionado; ?>&pagina=<?php echo $pagina_actual + 1; ?>&buscar="
                                class="btn" style="background:#6c757d; font-size: 13px; text-decoration:none;">Siguiente</a>
                        <?php else: ?>
                            <span class="btn"
                                style="background:#e9ecef; color:#adb5bd; font-size: 13px; cursor:not-allowed;">Siguiente</span>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>


            <br>

            <div style="display:flex;justify-content:flex-end;gap:10px;">

                <button type="button" class="btn btn-imprimir" onclick="window.print();">

                    🖨 Imprimir Reporte

                </button>

            </div>

        </div>

    </div>

</body>

</html>