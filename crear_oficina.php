<?php
session_start();
require 'conexion.php';

// Obtener la lista de trabajadores (Tipo 3) para el select
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
    <title>Registrar Oficina | UNJFSC</title>
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

        .btn-guardar {
            background: #198754;
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
            <h2 style="margin-top: 0; color: #7b1e3d;">+ Registrar Nueva Oficina</h2>
            <form action="guardar_oficina.php" method="POST">
                <div class="form-group">
                    <label>Nombre de la Oficina:</label>
                    <input type="text" name="nombre_oficina" class="form-control" required
                        placeholder="Ej: Dirección de Grados y Títulos">
                </div>
                <div class="form-group">
                    <label>Siglas:</label>
                    <input type="text" name="siglas" class="form-control" required placeholder="Ej: DGT"
                        style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Jefe de Oficina (Asignación Automática):</label>

                    <!-- Select de trabajadores existentes -->
                    <div id="seccion_select">
                        <select name="id_encargado" class="form-control">
                            <option value="">-- Seleccione un trabajador --</option>
                            <?php while ($t = mysqli_fetch_assoc($r_trabajadores)): ?>
                                <option value="<?php echo $t['id_usuario']; ?>">
                                    <?php echo htmlspecialchars($t['nombres'] . ' ' . $t['apellido_paterno'] . ' - ' . $t['cargo']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-guardar">Guardar Oficina</button>
                    <a href="Listado_Oficina.php" class="btn-cancelar">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>