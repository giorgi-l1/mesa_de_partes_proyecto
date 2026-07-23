<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';

// Obtener los tipos de trámite activos para el select
// Obtener SOLO los tipos de trámite activos para el select
$query_tipos = "SELECT id_tipo_tramite, nombre_tramite, descripcion 
                FROM tipos_tramite 
                WHERE estado = 1 
                ORDER BY id_tipo_tramite ASC";
$result_tipos = mysqli_query($cn, $query_tipos);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Trámite | UNJFSC</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="principal.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container-sm">
        <div class="panel">
            <h2 class="panel-title">Iniciar Nuevo Trámite</h2>

            <?php
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                if ($error == 'formato')
                    echo '<div class="alert" style="background:#f8d7da; color:#721c24;">El archivo adjunto debe ser obligatoriamente en formato PDF.</div>';
                if ($error == 'peso')
                    echo '<div class="alert" style="background:#f8d7da; color:#721c24;">El archivo excede el límite máximo de 5MB. Utilice el enlace externo.</div>';
                if ($error == 'db')
                    echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Error al registrar el trámite. Intente nuevamente.</div>';
            }
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                $exp = htmlspecialchars($_GET['exp']);
                echo '<div class="alert alert-success">¡Trámite enviado con éxito! Su número de expediente es: <strong>' . $exp . '</strong></div>';
            }
            ?>

            <form action="p_nuevo_tramite.php" method="POST" enctype="multipart/form-data">
                <div class="form-grid">

                    <div class="form-group full-width">
                        <label>Tipo de Trámite a Solicitar *</label>
                        <select name="id_tipo_tramite" required>
                            <option value="" disabled selected>-- Seleccione el trámite --</option>
                            <?php while ($row = mysqli_fetch_assoc($result_tipos)): ?>
                                <option value="<?php echo $row['id_tipo_tramite']; ?>">
                                    <?php echo $row['nombre_tramite']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Asunto (Resumen breve) *</label>
                        <input type="text" name="asunto" required placeholder="Ej: Solicitud de Constancia de Matrícula"
                            maxlength="250">
                    </div>

                    <div class="form-group full-width">
                        <label>Fundamentación del Trámite *</label>
                        <textarea name="descripcion_motivo" rows="1" required
                            placeholder="Explique detalladamente el motivo de su solicitud..."
                            style="overflow: hidden; resize: none; min-height: 48px;"
                            oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'"></textarea>
                    </div>
                    <div class="form-group full-width"
                        style="border-top: 1px solid #eee; padding-top: 0px; margin-top: 0;">
                        <h3 style="color: var(--dorado-arena); margin-bottom: 5px;">Sustento Adjunto</h3>
                    </div>

                    <div class="form-group">
                        <label>Archivo PDF (Máx. 5MB)</label>
                        <input type="file" name="documento" accept="application/pdf">
                        <span class="helper-text">* Solo formato .pdf. Opcional si envía un enlace externo.</span>
                    </div>

                    <div class="form-group">
                        <label>Enlace Externo (Opcional)</label>
                        <input type="url" name="enlace_externo" placeholder="Ej: https://drive.google.com/...">
                        <span class="helper-text">* Use esto si su documento pesa más de 5MB.</span>
                    </div>

                </div>

                <button type="submit" class="btn-submit">ENVIAR EXPEDIENTE A MESA DE PARTES</button>
            </form>
        </div>
    </div>
</body>

</html>