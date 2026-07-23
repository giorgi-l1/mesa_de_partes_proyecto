<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: Listado_Oficina.php");
    exit();
}

$id = intval($_GET['id']);
$query = "SELECT id_oficina, nombre_oficina, siglas FROM oficinas WHERE id_oficina = $id";
$resultado = mysqli_query($cn, $query);

if (mysqli_num_rows($resultado) == 0) {
    header("Location: Listado_Oficina.php");
    exit();
}
$ofi = mysqli_fetch_assoc($resultado);

// 1. Obtener al jefe actual (Rol 1)
$q_jefe = "SELECT id_usuario FROM datos_oficina_usuario WHERE id_oficina = $id AND id_rol_oficina = 1 LIMIT 1";
$r_jefe = mysqli_query($cn, $q_jefe);
$jefe_actual = mysqli_fetch_assoc($r_jefe);
$id_jefe_actual = $jefe_actual ? $jefe_actual['id_usuario'] : '';

// 2. Obtener lista de trabajadores para el select
$q_trabajadores = "SELECT 
                    u.id_usuario, 
                    dp.nombres, 
                    dp.apellido_paterno, 
                    dp.apellido_materno,
                    dp.numero_documento,
                    dper.cargo
                   FROM usuarios u 
                   LEFT JOIN datos_personal dper ON u.id_usuario = dper.id_usuario 
                   JOIN datos_personales dp ON u.id_usuario = dp.id_usuario 
                   WHERE u.id_tipo = 3 
                   AND u.estado = 1 
                   AND (dper.estado_asignado IS NULL OR dper.estado_asignado = '')";
$r_trabajadores = mysqli_query($cn, $q_trabajadores);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Oficina | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .formulario {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .btn-actualizar {
            background: #0d6efd;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancelar {
            background: #6c757d;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>

<body>
    <?php include 'cabecera_admin.php'; ?>

    <div class="container">
        <div class="formulario">
            <h2 style="margin-top: 0; color: #0d6efd;">✎ Editar Oficina</h2>
            <form action="actualizar_oficina.php" method="POST">

                <!-- ID Oculto para enviarlo al procesador -->
                <input type="hidden" name="id_oficina" value="<?php echo $ofi['id_oficina']; ?>">

                <div class="form-group">
                    <label>Nombre de la Oficina:</label>
                    <input type="text" name="nombre_oficina" class="form-control"
                        value="<?php echo htmlspecialchars($ofi['nombre_oficina']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Siglas:</label>
                    <input type="text" name="siglas" class="form-control"
                        value="<?php echo htmlspecialchars($ofi['siglas']); ?>" required
                        style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Cambiar Jefe de Oficina:</label>
                    <select name="id_encargado" class="form-control">
                        <option value="">-- Sin jefe asignado --</option>
                        <?php while ($t = mysqli_fetch_assoc($r_trabajadores)): ?>
                            <!-- Si el ID coincide con el jefe actual, lo marcamos como 'selected' -->
                            <option value="<?php echo $t['id_usuario']; ?>" <?php echo ($t['id_usuario'] == $id_jefe_actual) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellido_paterno'] . ' - ' . $t['cargo']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small style="color: #666;">Seleccione otro trabajador si desea transferir la jefatura.</small>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-actualizar">Actualizar Datos</button>
                    <a href="Listado_Oficina.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>