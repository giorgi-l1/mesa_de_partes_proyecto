<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php'; 

$id_usuario = $_SESSION["id_usuario"];

// Verificamos si ya existe una foto para este usuario
// Formatos admitidos en orden de prioridad
$formatos = ['jpg', 'jpeg', 'png'];
$foto_perfil = "";

foreach ($formatos as $ext) {
    $ruta_posible = "fotos/usuario_" . $id_usuario . "." . $ext;
    if (file_exists($ruta_posible)) {
        $foto_perfil = $ruta_posible;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Foto de Perfil | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Ajuste específico para centrar la visualización de la foto */
        .preview-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 25px;
        }
        .profile-preview {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 4px solid var(--dorado-arena);
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            background-color: #f8f9fa;
        }
        .profile-icon-placeholder {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 4px dashed var(--dorado-arena);
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: #adb5bd;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Perfil</span></div>
        <div class="nav-links">
            <a href="principal.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container-sm">
        <div class="panel">
            <h2 class="panel-title">Actualizar Foto de Perfil</h2>

            <?php
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                if ($error == 'formato') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Solo se permiten formatos JPG, JPEG o PNG.</div>';
                if ($error == 'tamano') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">La imagen excede el tamaño máximo permitido (2MB).</div>';
                if ($error == 'subida') echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Hubo un problema al guardar el archivo. Inténtalo de nuevo.</div>';
            }
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                echo '<div class="alert alert-success">¡Tu foto de perfil ha sido actualizada con éxito!</div>';
            }
            ?>

            <div class="preview-container">
                <?php if (!empty($foto_perfil)): ?>
                    <img src="<?php echo $foto_perfil . '?v=' . time(); ?>" alt="Foto de perfil" class="profile-preview">
                <?php else: ?>
                    <div class="profile-icon-placeholder">&#128100;</div>
                <?php endif; ?>
                <p style="margin-top: 10px; font-size: 0.9rem; color: #666;">Vista previa actual</p>
            </div>

            <form action="p_perfil_foto.php" method="POST" enctype="multipart/form-data">
                <div class="form-group full-width">
                    <label>Selecciona una nueva imagen</label>
                    <input type="file" name="foto" accept="image/jpeg, image/jpg, image/png" required>
                    <span class="helper-text">* Formatos aceptados: JPG, JPEG, PNG. Tamaño máximo recomendado: 2MB.</span>
                </div>
                
                <button type="submit" class="btn-submit">SUBIR E INSTALAR FOTO</button>
            </form>
        </div>
    </div>
</body>
</html>