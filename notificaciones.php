<?php
// Importamos las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/lib/phpmailer/Exception.php';
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';

function notificar_usuario($cn, $id_usuario, $tipo_alerta, $expediente, $mensaje_extra = "")
{

    // 1. Obtener los datos del usuario, buscando tanto en datos personales como jurídicos
    $sql = "SELECT 
                u.correo AS correo_institucional,
                dp.correo_personal,
                dj.correo_empresarial,
                c.* 
            FROM usuarios u 
            LEFT JOIN configuracion_alertas c ON u.id_usuario = c.id_usuario 
            LEFT JOIN datos_personales dp ON u.id_usuario = dp.id_usuario
            LEFT JOIN datos_juridicos dj ON u.id_usuario = dj.id_usuario
            WHERE u.id_usuario = '$id_usuario'";

    $res = mysqli_query($cn, $sql);

    if (!$res || mysqli_num_rows($res) == 0) {
        return false;
    }

    $datos = mysqli_fetch_assoc($res);

    // 2. Determinar qué correo usar (Prioridad: Personal -> Empresarial -> Institucional)
    $correo = '';
    if (!empty($datos['correo_personal'])) {
        $correo = $datos['correo_personal'];
    } elseif (!empty($datos['correo_empresarial'])) {
        $correo = $datos['correo_empresarial'];
    } else {
        $correo = $datos['correo_institucional']; // Respaldo por si no hay otro
    }

    // Si al final no hay un correo válido al cual enviar, abortamos
    if (empty($correo)) {
        error_log("Error: No se encontró ningún correo válido para el usuario ID: $id_usuario");
        return false;
    }
    $telefono = $datos['telefono_whatsapp']; // Ej: +51999888777

    // --- NUEVO: FORMATEAR NÚMERO A PERÚ ---
    // Limpiamos espacios y si tiene exactamente 9 dígitos, le agregamos el 51
    $telefono = trim($telefono);
    if (strlen($telefono) == 9) {
        $telefono = '51' . $telefono;
    }
    // 2. Verificar si el usuario ACTIVÓ esta alerta específica en la Base de Datos
    $quiere_alerta = false;
    if ($tipo_alerta == 'derivado' && isset($datos['alerta_derivado']) && $datos['alerta_derivado'] == 1)
        $quiere_alerta = true;
    if ($tipo_alerta == 'rechazado' && isset($datos['alerta_rechazado']) && $datos['alerta_rechazado'] == 1)
        $quiere_alerta = true;
    if ($tipo_alerta == 'finalizado' && isset($datos['alerta_finalizado']) && $datos['alerta_finalizado'] == 1)
        $quiere_alerta = true;

    // Si no quiere alertas, terminamos la función sin hacer nada
    if (!$quiere_alerta)
        return true;

    // --- MAGIA: CONSTRUIR EL HISTORIAL PARA EL CORREO ---
    // Buscar el id del trámite basado en el expediente
    $q_tramite = mysqli_query($cn, "SELECT id_tramite, asunto FROM tramites WHERE numero_expediente = '$expediente'");
    $d_tramite = mysqli_fetch_assoc($q_tramite);
    $id_tramite = $d_tramite['id_tramite'];
    $asunto_tramite = htmlspecialchars($d_tramite['asunto']);

    // Buscar todos los movimientos
    // Buscar todos los movimientos (CAMBIAMOS fecha_movimiento por fecha_envio)
    $q_movs = mysqli_query($cn, "SELECT m.fecha_envio, o.nombre_oficina, e.nombre_estado, m.observaciones 
                                 FROM movimientos_tramite m
                                 LEFT JOIN oficinas o ON m.id_oficina_destino = o.id_oficina
                                 LEFT JOIN estados_tramite e ON m.id_estado_mov = e.id_estado
                                 WHERE m.id_tramite = '$id_tramite' ORDER BY m.numero_movimiento ASC");

    $tabla_historial = '
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-family: Arial, sans-serif;">
        <thead>
            <tr style="background-color: #1a2b4c; color: white;">
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Fecha</th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Área</th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Estado</th>
                <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Observación</th>
            </tr>
        </thead>
        <tbody>';

    while ($mov = mysqli_fetch_assoc($q_movs)) {
        // AQUÍ TAMBIÉN CAMBIAMOS: Extraemos 'fecha_envio' del array
        $fecha = date("d/m/Y H:i", strtotime($mov['fecha_envio']));
        $oficina = htmlspecialchars($mov['nombre_oficina']);
        $estado = htmlspecialchars($mov['nombre_estado']);
        $obs = htmlspecialchars($mov['observaciones']);

        $tabla_historial .= "
            <tr style='background-color: #f9f9f9;'>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 13px;'>$fecha</td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 13px;'><strong>$oficina</strong></td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 13px;'>$estado</td>
                <td style='padding: 10px; border: 1px solid #ddd; font-size: 13px;'>$obs</td>
            </tr>";
    }
    $tabla_historial .= '</tbody></table>';

    // --- CONSTRUIR EL CORREO FORMAL ---
    $asunto = "Notificación Oficial UNJFSC - Trámite $expediente";

    $cuerpo_correo = "
    <div style='max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif;'>
        <div style='background-color: #1a2b4c; color: white; padding: 20px; text-align: center;'>
            <h2 style='margin: 0;'>MESA DE PARTES VIRTUAL</h2>
            <p style='margin: 5px 0 0 0; font-size: 14px;'>Universidad Nacional José Faustino Sánchez Carrión</p>
        </div>
        
        <div style='padding: 30px; color: #333;'>
            <h3 style='color: #1a2b4c; margin-top:0;'>Estimado(a) Usuario(a),</h3>
            <p>Se ha registrado una nueva actualización en su trámite con expediente <strong>$expediente</strong> (Asunto: <em>$asunto_tramite</em>).</p>
            
            <div style='background-color: #e9ecef; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>
                <strong>Mensaje del Sistema:</strong><br>
                $mensaje_extra
            </div>
            
            <h4 style='color: #1a2b4c; margin-bottom: 10px;'>Historial Electrónico del Trámite:</h4>
            $tabla_historial
            
            <p style='margin-top: 30px; font-size: 12px; color: #777;'>
                * Este es un mensaje generado automáticamente. Por favor, no responda a este correo.
            </p>
        </div>
    </div>";
    // Convertir etiquetas HTML al formato de WhatsApp
    $mensaje_limpio_wa = str_replace(array('<b>', '</b>', '<strong>', '</strong>'), '*', $mensaje_extra);
    $mensaje_limpio_wa = str_replace(array('<br>', '<br/>', '<br />'), "\n", $mensaje_limpio_wa);
    // Quitar cualquier otra etiqueta HTML residual
    $mensaje_limpio_wa = strip_tags($mensaje_limpio_wa);

    $texto_whatsapp = "🔔 *MESA DE PARTES UNJFSC*\nHola, tu trámite *$expediente* ha sido actualizado.\n\n$mensaje_limpio_wa";
    // 3. ENVIAR CORREO
    if (isset($datos['recibir_emails']) && $datos['recibir_emails'] == 1 && !empty($correo)) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'unjfscnotificacion@gmail.com';
            $mail->Password = 'cksoggjjeempbxta';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->CharSet = 'UTF-8'; // <--- LA SOLUCIÓN A LOS SÍMBOLOS RAROS

            $mail->setFrom('unjfscnotificacion@gmail.com', 'Mesa de Partes UNJFSC');
            $mail->addAddress($correo);
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body = $cuerpo_correo;
            $mail->send();
        } catch (Exception $e) {
        } catch (Exception $e) {
            // OPCIÓN A: Guardar el error en el log de PHP (Seguro para producción)
            error_log("Error de PHPMailer al intentar enviar a $correo: " . $mail->ErrorInfo);

            // OPCIÓN B: Imprimir el error en pantalla (Ideal para depurar ahora mismo)
            echo "<div style='background: #ffcccc; color: #cc0000; padding: 10px; margin: 10px 0; border: 1px solid #cc0000; border-radius: 5px;'>
                    <strong>Error de Envío de Correo:</strong> {$mail->ErrorInfo}
                  </div>";

        }
    }

    // 4. ENVIAR WHATSAPP (CallMeBot)
    if (isset($datos['recibir_whatsapp']) && $datos['recibir_whatsapp'] == 1 && !empty($telefono)) {
        $apikey = "8855358";

        // Formatear el mensaje para URL
        $texto_url = urlencode($texto_whatsapp);
        $url_bot = "https://api.callmebot.com/whatsapp.php?phone=$telefono&text=$texto_url&apikey=$apikey";

        // Ejecutar el envío (Desactivamos SSL local para evitar errores de XAMPP)
        $opciones = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];
        $contexto = stream_context_create($opciones);
        @file_get_contents($url_bot, false, $contexto);
    }
}
?>