<?php
session_start();

// Validar que sea un administrador o personal de mesa de partes
if (!isset($_SESSION["auth_mesa"]) || $_SESSION["auth_mesa"] != "1") {
    header("Location: ../login_mesa.php");
    exit();
}

require '../conexion.php';

// ----------------------------------------------------
// 1. OBTENER DATOS PARA LOS FILTROS
// ----------------------------------------------------
// Obtener lista de oficinas activas
$query_oficinas = "SELECT id_oficina, nombre_oficina FROM oficinas ORDER BY nombre_oficina ASC";
$res_oficinas = mysqli_query($cn, $query_oficinas);

// Obtener lista de tipos de usuario desde la BD
$query_tipos = "SELECT id_tipo, nombre_tipo FROM tipos_usuario ORDER BY nombre_tipo ASC";
$res_tipos = mysqli_query($cn, $query_tipos);

// ----------------------------------------------------
// 2. PROCESAR LA BÚSQUEDA
// ----------------------------------------------------
$busqueda = isset($_GET["termino"]) ? mysqli_real_escape_string($cn, trim($_GET["termino"])) : "";
$filtro_tipo = isset($_GET["tipo_usuario"]) ? intval($_GET["tipo_usuario"]) : 0;
$filtro_oficina = isset($_GET["id_oficina"]) ? intval($_GET["id_oficina"]) : 0;

$where_clauses = [];

if ($busqueda !== "") {
    $where_clauses[] = "(t.numero_expediente LIKE '%$busqueda%' OR t.asunto LIKE '%$busqueda%')";
}
if ($filtro_tipo > 0) {
    // Dependiendo de tu BD, la columna suele ser id_tipo o id_tipo_usuario en la tabla usuarios
    $where_clauses[] = "u.id_tipo = $filtro_tipo";
}
if ($filtro_oficina > 0) {
    $where_clauses[] = "t.id_oficina_actual = $filtro_oficina";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// ----------------------------------------------------
// 3. CONSULTA PRINCIPAL
// ----------------------------------------------------
$sql = "SELECT t.id_tramite, t.numero_expediente, u.correo, u.id_tipo, tt.nombre_tramite, 
               t.asunto, o.nombre_oficina, e.nombre_estado, t.fecha_envio
        FROM tramites t
        INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
        INNER JOIN tipos_tramite tt ON t.id_tipo_tramite = tt.id_tipo_tramite
        INNER JOIN oficinas o ON t.id_oficina_actual = o.id_oficina
        INNER JOIN estados_tramite e ON t.id_estado = e.id_estado
        $where_sql
        ORDER BY t.fecha_envio DESC
        LIMIT 100"; // Límite para no sobrecargar si no hay filtros

$resultado = mysqli_query($cn, $sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Trámites | Mesa de Partes</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .filtros-avanzados {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 25px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
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
            height: 40px;
        }

        .btn:hover {
            opacity: .9;
        }

        .btn-buscar {
            background: #007bff;
        }

        .btn-limpiar {
            background: #6c757d;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabla th {
            background: #1a2b4c;
            color: white;
            padding: 12px;
            font-size: 13px;
        }

        .tabla td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }

        .estado {
            display: inline-block;
            width: 130px;
            padding: 6px;
            border-radius: 20px;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .tipo-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
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
                    <a href="ver_tramites.php" class="active">Búsqueda de Trámites</a>
                    <a href="../reporte/reporte_fecha.php">Reportes por Fecha</a>
                    <a href="../mesa_ayuda_mesa.php">Mesa de Ayuda</a>
                </div>
            </div>

            <a href="../cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container">
        <div class="panel">
            <h2 class="panel-title">Búsqueda Avanzada de Trámites</h2>
            <p style="margin-bottom:20px; color: #555;">Utilice los filtros a continuación para localizar trámites
                específicos según el usuario, área o expediente.</p>

            <form method="GET" action="ver_tramites.php">
                <div class="filtros-avanzados">
                    <div class="form-group">
                        <label>Buscar por Expediente o Asunto</label>
                        <input type="text" name="termino" class="form-control"
                            placeholder="Ej: EXP-2023-001 o 'Constancia'"
                            value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>

                    <div class="form-group">
                        <label>Tipo de Usuario</label>
                        <select name="tipo_usuario" class="form-control">
                            <option value="0">-- Todos --</option>
                            <?php while ($tipo = mysqli_fetch_assoc($res_tipos)): ?>
                                <option value="<?php echo $tipo['id_tipo']; ?>" <?php if ($filtro_tipo == $tipo['id_tipo'])
                                       echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($tipo['nombre_tipo']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Área / Oficina Actual</label>
                        <select name="id_oficina" class="form-control">
                            <option value="0">-- Todas las Áreas --</option>
                            <?php while ($of = mysqli_fetch_assoc($res_oficinas)): ?>
                                <option value="<?php echo $of['id_oficina']; ?>" <?php if ($filtro_oficina == $of['id_oficina'])
                                       echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($of['nombre_oficina']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="buscar" class="btn btn-buscar">🔍 Buscar</button>
                        <a href="ver_tramites.php" class="btn btn-limpiar" title="Limpiar Filtros">✖</a>
                    </div>
                </div>
            </form>

            <div class="table-container">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Expediente</th>
                            <th>Tipo Usuario</th>
                            <th>Correo / Solicitante</th>
                            <th>Tipo Trámite</th>
                            <th>Asunto</th>
                            <th>Área Actual</th>
                            <th>Estado</th>
                            <th>Fecha de Envío</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)):
                                // Color del Estado
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

                                // Etiqueta del tipo de usuario basado en id_tipo
                                $txt_tipo = "Desconocido";
                                if ($fila["id_tipo"] == 1)
                                    $txt_tipo = "Alumno";
                                if ($fila["id_tipo"] == 2)
                                    $txt_tipo = "Docente/Pers.";
                                if ($fila["id_tipo"] == 3)
                                    $txt_tipo = "Egresado";
                                if ($fila["id_tipo"] == 4)
                                    $txt_tipo = "Inst. Externa";
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($fila["numero_expediente"]); ?></strong></td>
                                    <td><span class="tipo-badge"><?php echo $txt_tipo; ?></span></td>
                                    <td><?php echo htmlspecialchars($fila["correo"]); ?></td>
                                    <td><?php echo htmlspecialchars($fila["nombre_tramite"]); ?></td>
                                    <td><?php echo htmlspecialchars($fila["asunto"]); ?></td>
                                    <td><?php echo htmlspecialchars($fila["nombre_oficina"]); ?></td>
                                    <td>
                                        <span class="estado" style="background:<?php echo $color; ?>;">
                                            <?php echo htmlspecialchars($fila["nombre_estado"]); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($fila["fecha_envio"])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:40px; color:#777;">
                                    <?php echo ($where_sql != "") ? "No se encontraron trámites que coincidan con los filtros seleccionados." : "Utiliza el buscador superior para encontrar trámites."; ?>
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