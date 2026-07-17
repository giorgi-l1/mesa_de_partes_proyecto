<?php
// Llamamos a tu archivo de conexión
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Recibir los datos generales
    $id_tipo = $_POST['tipo_usuario'];
    $correo_contacto = $_POST['correo'];
    $password = $_POST['password'];

    // 2. LÓGICA PARA CREAR EL IDENTIFICADOR DEL SISTEMA (Alineado 1 al 5)
    $identificador = "";

    if ($id_tipo == '1') {
        // Persona Natural usa su número de documento (DNI) como usuario
        $identificador = $_POST['num_doc'];
    } elseif ($id_tipo == '2') {
        $identificador = $_POST['cod_universitario'];
    } elseif ($id_tipo == '3') {
        $identificador = $_POST['cod_admin'];
    } elseif ($id_tipo == '4') {
        $identificador = $_POST['ruc'];
    } elseif ($id_tipo == '5') {
        $identificador = $_POST['cod_egresado'];
    }

    // Armamos el correo oficial para que inicie sesión
    $correo_sistema = $identificador . "@unjfsc.edu.pe";

    // Iniciar transacción
    $cn->begin_transaction();

    try {
        // PASO A: Insertar en la tabla principal 'usuarios'
        $sql_usuario = "INSERT INTO usuarios (correo, password, id_tipo) VALUES ('$correo_sistema', '$password', '$id_tipo')";
        $cn->query($sql_usuario);

        $id_nuevo_usuario = $cn->insert_id;

        // PASO B: Guardar Datos Personales (Aplica a todos excepto Institución)
        if (in_array($id_tipo, ['1', '2', '3', '5'])) {

            $nombres = $_POST['nombres'];
            $ape_paterno = $_POST['ape_paterno'];
            $ape_materno = $_POST['ape_materno'];
            $tipo_doc = $_POST['tipo_doc'];
            $num_doc = $_POST['num_doc'];
            $celular = $_POST['celular'];
            $telefono_fijo = $_POST['telefono_fijo'];

            $direccion_texto = $_POST['tipo_via'] . ' ' . $_POST['nombre_via'];
            $id_distrito = $_POST['id_distrito'];
            $referencia = $_POST['referencia'];

            $sql_personal = "INSERT INTO datos_personales 
                            (id_usuario, nombres, apellido_paterno, apellido_materno, tipo_documento, numero_documento, celular, telefono_fijo, correo_personal, direccion_texto, id_distrito, referencia_direccion) 
                            VALUES 
                            ('$id_nuevo_usuario', '$nombres', '$ape_paterno', '$ape_materno', '$tipo_doc', '$num_doc', '$celular', '$telefono_fijo', '$correo_contacto', '$direccion_texto', '$id_distrito', '$referencia')";
            $cn->query($sql_personal);
        }

        // PASO C: Guardar los datos específicos según el perfil
        if ($id_tipo == '2') { // ALUMNO
            $anio_ingreso = $_POST['anio_ingreso'];
            $ciclo = $_POST['ciclo'];
            $id_escuela = $_POST['id_escuela'];

            $sql_alumno = "INSERT INTO datos_alumnos (id_usuario, codigo_universitario, id_escuela, año_ingreso, ciclo_actual) 
                           VALUES ('$id_nuevo_usuario', '$identificador', '$id_escuela', '$anio_ingreso', '$ciclo')";
            $cn->query($sql_alumno);

        } elseif ($id_tipo == '3') { // PERSONAL / DOCENTE
            $cargo = $_POST['cargo'];
            $condicion = $_POST['condicion'];
            $categoria = $_POST['categoria'];

            $sql_personal_lab = "INSERT INTO datos_personal (id_usuario, codigo_administrativo, cargo, condicion_laboral, nivel_categoria) 
                                 VALUES ('$id_nuevo_usuario', '$identificador', '$cargo', '$condicion', '$categoria')";
            $cn->query($sql_personal_lab);

        } elseif ($id_tipo == '4') { // INSTITUCIÓN EXTERNA
            $razon_social = $_POST['razon_social'];
            $direccion_inst = $_POST['direccion_inst'];
            $id_distrito_inst = $_POST['id_distrito'];

            $sql_institucion = "INSERT INTO datos_juridicos (id_usuario, ruc, razon_social, dir_empresa, id_distrito, correo_empresarial) 
                                VALUES ('$id_nuevo_usuario', '$identificador', '$razon_social', '$direccion_inst', '$id_distrito_inst', '$correo_contacto')";
            $cn->query($sql_institucion);

        } elseif ($id_tipo == '5') { // EGRESADO
            $anio_ingreso = $_POST['anio_ingreso_egreso'];
            $anio_egreso = $_POST['anio_egreso'];
            $id_escuela_egre = $_POST['id_escuela_egresado'];

            $sql_egresado = "INSERT INTO datos_egresados (id_usuario, codigo_universitario, id_escuela, año_ingreso, año_egreso) 
                             VALUES ('$id_nuevo_usuario', '$identificador', '$id_escuela_egre', '$anio_ingreso', '$anio_egreso')";
            $cn->query($sql_egresado);
        }

        // Confirmamos todos los INSERTS
        $cn->commit();

        // Redirigimos avisándole cuál es su usuario
        echo "<script>
                alert('Registro exitoso. Para iniciar sesión usa tu identificador: $identificador');
                window.location.href = 'index.php';
              </script>";

    } catch (Exception $e) {
        $cn->rollback();
        echo "Error en la Base de Datos: " . $e->getMessage();
    }
}

$cn->close();
?>