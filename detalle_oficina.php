<?php
session_start();
require 'conexion.php';

if (!isset($_GET['id'])) {
    header("Location: Listado_Oficina.php");
    exit();
}

$id = intval($_GET['id']);

// 1. Obtener datos de la oficina
$q_oficina = "SELECT nombre_oficina, siglas, estado FROM oficinas WHERE id_oficina = $id";
$r_oficina = mysqli_query($cn, $q_oficina);

if (!$r_oficina || mysqli_num_rows($r_oficina) == 0) {
    header("Location: Listado_Oficina.php");
    exit();
}
$oficina = mysqli_fetch_assoc($r_oficina);

// 2. Obtener el personal base asignado a esta oficina
$q_personal = "SELECT dp.nombres, dp.apellido_paterno, dp.apellido_materno, dp.numero_documento as dni, u.correo, dou.cargo_real
               FROM datos_oficina_usuario dou
               INNER JOIN usuarios u ON dou.id_usuario = u.id_usuario
               INNER JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
               WHERE dou.id_oficina = $id AND u.estado = 1";
$r_personal = mysqli_query($cn, $q_personal);

// 3. Obtener credenciales de la cuenta operativa de la Oficina (Tipo 7)
// 3. Obtener credenciales de la cuenta operativa de la Oficina (Tipo 7)
$q_credenciales = "SELECT u.id_usuario, u.correo, u.password 
                   FROM usuarios u
                   INNER JOIN datos_oficina_usuario dou ON u.id_usuario = dou.id_usuario
                   WHERE dou.id_oficina = $id AND dou.id_rol_oficina = 1 
                   LIMIT 1";
$res_credenciales = mysqli_query($cn, $q_credenciales);

if ($cuenta = mysqli_fetch_assoc($res_credenciales)) {
    $correo_mostrar = $cuenta['correo'];
    $password_mostrar = $cuenta['password']; // Mostrará 12345
} else {
    $correo_mostrar = "No asignado";
    $password_mostrar = "N/A";
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Detalles de Oficina | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #7b1e3d;
            margin-top: 0;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-activo {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-inactivo {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-volver {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <?php include 'cabecera_admin.php'; ?>
    <div class="container">
        <a href="Listado_Oficina.php" class="btn-volver">← Volver al Listado</a>
        <div class="card">
            <h2>🏢 <?php echo htmlspecialchars($oficina['nombre_oficina']); ?>
                (<?php echo htmlspecialchars($oficina['siglas']); ?>)</h2>
            <p><strong>Estado en el sistema:</strong>
                <?php if ($oficina['estado'] == 1): ?>
                    <span class="badge badge-activo">Activo</span>
                <?php else: ?>
                    <span class="badge badge-inactivo">Inactivo</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="card table-container">
            <h3>👥 Personal Asignado y Credenciales</h3>
            <table>
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>NOMBRES Y APELLIDOS</th>
                        <th>CORREO OPERATIVO (OFICINA)</th>
                        <th>CONTRASEÑA</th>
                        <th>CARGO REAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($r_personal && mysqli_num_rows($r_personal) > 0): ?>
                        <?php while ($persona = mysqli_fetch_assoc($r_personal)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($persona['dni'] ?? 'N/A'); ?></td>
                                <td><strong><?php echo htmlspecialchars($persona['nombres'] . ' ' . $persona['apellido_paterno'] . ' ' . ($persona['apellido_materno'] ?? '')); ?></strong>
                                </td>
                                <td><strong><?php echo htmlspecialchars($correo_mostrar); ?></strong></td>
                                <td>
                                    <span
                                        style="display: block; margin-bottom: 5px;"><?php echo htmlspecialchars($password_mostrar); ?></span>

                                    <?php if (isset($cuenta['id_usuario'])): ?>
                                        <form action="cambiar_pass_oficina.php" method="POST"
                                            style="display: flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="id_cuenta_ofi" value="<?php echo $cuenta['id_usuario']; ?>">
                                            <input type="hidden" name="id_oficina" value="<?php echo $id; ?>">
                                            <input type="password" name="nueva_password" placeholder="Nueva clave" required
                                                style="padding: 4px; width: 100px; border: 1px solid #ccc; border-radius: 4px;">
                                            <button type="submit"
                                                style="background: #0d6efd; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">Cambiar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($persona['cargo_real'] ?? 'Sin cargo asignado'); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #777; padding: 20px;">No hay personal asignado
                                o activo en esta oficina.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>