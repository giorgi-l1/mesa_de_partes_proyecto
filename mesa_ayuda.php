<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1") {
    header("Location: index.php");
    exit();
}

require 'conexion.php';
$id_usuario = $_SESSION["id_usuario"];

// Obtener Tipos de Ticket
$query_tipos = "SELECT * FROM tipos_ticket ORDER BY nombre_tipo ASC";
$result_tipos = mysqli_query($cn, $query_tipos);

// Obtener Trámites del Usuario (Para el selector opcional)
$query_tramites = "SELECT id_tramite, numero_expediente, asunto FROM tramites WHERE id_usuario = '$id_usuario' ORDER BY fecha_envio DESC";
$result_tramites = mysqli_query($cn, $query_tramites);

// Obtener Oficinas (Para el selector opcional)
$query_oficinas = "SELECT id_oficina, nombre_oficina FROM oficinas ORDER BY nombre_oficina ASC";
$result_oficinas = mysqli_query($cn, $query_oficinas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesa de Ayuda | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Ayuda</span></div>
        <div class="nav-links">
            <a href="principal.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="container-sm">
        <div class="panel">
            <h2 class="panel-title">Centro de Soporte y Reclamos</h2>

            <?php
            if (isset($_GET['error'])) {
                echo '<div class="alert" style="background:#f8d7da; color:#721c24;">Error al registrar el ticket. Intente nuevamente.</div>';
            }
            if (isset($_GET['status']) && $_GET['status'] == 'success') {
                $tck = htmlspecialchars($_GET['tck']);
                echo '<div class="alert alert-success">¡Ticket enviado con éxito! Su código de seguimiento es: <strong>' . $tck . '</strong>. Nuestro equipo le responderá a la brevedad.</div>';
            }
            ?>

            <form action="p_mesa_ayuda.php" method="POST">
                <div class="form-grid">
                    
                    <div class="form-group full-width">
                        <label>Tipo de Reporte *</label>
                        <select name="id_tipo_ticket" required>
                            <option value="" disabled selected>-- Seleccione una opción --</option>
                            <?php while($row = mysqli_fetch_assoc($result_tipos)): ?>
                                <option value="<?php echo $row['id_tipo_ticket']; ?>">
                                    <?php echo $row['nombre_tipo']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group full-width" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px;">
                        <h3 style="color: var(--dorado-arena); margin-bottom: 10px; font-size: 1.1rem;">Asociación Opcional</h3>
                        <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">Llene estos campos solo si su reporte está relacionado con un trámite específico o una oficina en particular.</p>
                    </div>

                    <div class="form-group">
                        <label>Expediente Relacionado (Opcional)</label>
                        <select name="id_tramite">
                            <option value="">-- No asociar a ningún trámite --</option>
                            <?php while($row = mysqli_fetch_assoc($result_tramites)): ?>
                                <option value="<?php echo $row['id_tramite']; ?>">
                                    <?php echo $row['numero_expediente'] . " - " . substr($row['asunto'], 0, 25) . "..."; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Oficina Relacionada (Opcional)</label>
                        <select name="id_oficina">
                            <option value="">-- No asociar a ninguna oficina --</option>
                            <?php while($row = mysqli_fetch_assoc($result_oficinas)): ?>
                                <option value="<?php echo $row['id_oficina']; ?>">
                                    <?php echo $row['nombre_oficina']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group full-width" style="margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px;"></div>

                    <div class="form-group full-width">
                        <label>Asunto del Ticket *</label>
                        <input type="text" name="asunto" required placeholder="Ej: Problemas técnicos al adjuntar mi archivo" maxlength="250">
                    </div>

                    <div class="form-group full-width">
                        <label>Descripción Detallada *</label>
                        <textarea name="descripcion_problema" rows="5" required placeholder="Explique su consulta, queja o sugerencia de la forma más clara posible..."></textarea>
                    </div>

                </div>
                
                <button type="submit" class="btn-submit">ENVIAR TICKET DE AYUDA</button>
            </form>
        </div>
    </div>
</body>
</html>