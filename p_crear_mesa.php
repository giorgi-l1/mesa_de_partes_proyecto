<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_trabajador_base = (int) $_POST['id_trabajador_base'];
    $ventanilla = (int) $_POST['numero_ventanilla'];
    $password_mesa = mysqli_real_escape_string($cn, $_POST['password_mesa']);
    $nombre_asignacion = "Mesa " . $ventanilla;

    // 1. Obtener los datos del trabajador base
    $q_base = "SELECT * FROM datos_personales WHERE id_usuario = $id_trabajador_base";
    $res_base = mysqli_query($cn, $q_base);

    if ($base = mysqli_fetch_assoc($res_base)) {

        $nombres = $base['nombres'];
        $ape_pat = $base['apellido_paterno'];
        $ape_mat = $base['apellido_materno'];
        $tipo_doc = $base['tipo_documento'];
        $num_doc = $base['numero_documento'];
        $celular = $base['celular'];

        // 2. Extraer las primeras 3 letras
        $nom_limpio = strtolower(str_replace(' ', '', $nombres));
        $ape_limpio = strtolower(str_replace(' ', '', $ape_pat));
        $tres_nom = substr($nom_limpio, 0, 3);
        $tres_ape = substr($ape_limpio, 0, 3);

        // 3. Crear la cuenta OPERATIVA (id_tipo = 6) con correo temporal
        $correo_temporal = "temp_mesa_" . time() . "@unjfsc.edu.pe";
        $q_user = "INSERT INTO usuarios (correo, password, id_tipo, estado) 
                   VALUES ('$correo_temporal', '$password_mesa', 6, 1)";

        if (mysqli_query($cn, $q_user)) {
            $id_nueva_mesa = mysqli_insert_id($cn);

            // 4. Formar el correo final y actualizar la nueva cuenta
            $correo_final = "mesa{$ventanilla}{$tres_nom}{$tres_ape}.{$id_nueva_mesa}@unjfsc.edu.pe";
            mysqli_query($cn, "UPDATE usuarios SET correo = '$correo_final' WHERE id_usuario = $id_nueva_mesa");

            // 5. Replicar los datos personales para la cuenta de la Mesa
            $q_dp_mesa = "INSERT INTO datos_personales (id_usuario, nombres, apellido_paterno, apellido_materno, tipo_documento, numero_documento, celular)
                          VALUES ($id_nueva_mesa, '$nombres', '$ape_pat', '$ape_mat', '$tipo_doc', '$num_doc', '$celular')";
            mysqli_query($cn, $q_dp_mesa);

           // 6. Registrar los datos_personal para la nueva cuenta de Mesa
            $cod_admin = "VENT-" . $id_nueva_mesa;
            mysqli_query($cn, "INSERT INTO datos_personal (id_usuario, codigo_administrativo, cargo, estado_asignado) VALUES ($id_nueva_mesa, '$cod_admin', 'Ventanilla $ventanilla', '$nombre_asignacion')");

            // 7. BLOQUEAR al trabajador base (marcarlo como ocupado)
            $check_dper = mysqli_query($cn, "SELECT id_usuario FROM datos_personal WHERE id_usuario = $id_trabajador_base");
            if (mysqli_num_rows($check_dper) > 0) {
                mysqli_query($cn, "UPDATE datos_personal SET estado_asignado = '$nombre_asignacion' WHERE id_usuario = $id_trabajador_base");
            } else {
                $cod_base = "TRAB-" . $id_trabajador_base;
                mysqli_query($cn, "INSERT INTO datos_personal (id_usuario, codigo_administrativo, cargo, estado_asignado) VALUES ($id_trabajador_base, '$cod_base', 'Personal de Apoyo', '$nombre_asignacion')");
            }

            // 8. Asignar al nuevo usuario de ventanilla a la UTD Central (Oficina 1)
            mysqli_query($cn, "INSERT INTO datos_oficina_usuario (id_usuario, id_oficina, id_rol_oficina, cargo_real) VALUES ($id_nueva_mesa, 1, 3, 'Ventanilla $ventanilla')");
            
            // Éxito: volvemos al listado
            header("Location: gestion_mesas.php?msg=asignado");
            exit();
        } else {
            die("Error al crear la cuenta de mesa.");
        }
    } else {
        die("Error: No se encontraron los datos del trabajador base.");
    }
} else {
    header("Location: gestion_mesas.php");
    exit();
}
?>