<?php
require 'conexion.php';

// 1. Consultar escuelas (Se queda exactamente IGUAL)
$query_escuelas = "SELECT id_escuela, nombre_escuela FROM escuelas_profesionales ORDER BY nombre_escuela ASC";
$result_escuelas = $cn->query($query_escuelas);

$escuelas = [];
if ($result_escuelas) {
    while ($row = $result_escuelas->fetch_assoc()) {
        $escuelas[] = $row;
    }
}

// 2. Consultar departamentos (CORREGIDO: Apunta a tu tabla real y sus columnas id, name)
$query_deps = "SELECT id, name FROM ubigeo_peru_departments ORDER BY name ASC";
$result_deps = $cn->query($query_deps);

$departamentos = [];
if ($result_deps) {
    while ($row = $result_deps->fetch_assoc()) {
        $departamentos[] = $row; // Guardará arreglos con las llaves 'id' y 'name'
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Mesa de Partes UNJFSC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Montserrat:wght@300;400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/estilos.css">

</head>

<body>
    <div class="overlay"></div>

    <div class="login-card registro-card">
        <div class="login-header">
            <h1>Registro de Usuario</h1>
            <p>Seleccione su perfil para continuar</p>
        </div>

        <form action="p_registro.php" method="POST">

            <div class="form-group full-width">
                <label for="tipo_usuario">Tipo de Usuario *</label>
                <select id="tipo_usuario" name="tipo_usuario" required onchange="mostrarCampos()">
                    <option value="" disabled selected>-- Seleccione una opción --</option>
                    <option value="1">Alumno</option>
                    <option value="2">Personal / Docente</option>
                    <option value="3">Egresado</option>
                    <option value="4">Institución Externa</option>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="correo">Correo Electrónico (Contacto Personal/Empresarial) *</label>
                    <input type="email" id="correo" name="correo" required>
                </div>
                <div class="form-group full-width">
                    <label for="password">Contraseña (Para acceder al sistema) *</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div id="seccion_personales" class="form-grid seccion-dinamica">
                <div class="seccion-titulo"><strong>Datos Personales y de Contacto</strong></div>

                <div class="form-group full-width">
                    <label for="nombres">Nombres Completos</label>
                    <input type="text" id="nombres" name="nombres">
                </div>
                <div class="form-group">
                    <label for="ape_paterno">Apellido Paterno</label>
                    <input type="text" id="ape_paterno" name="ape_paterno">
                </div>
                <div class="form-group">
                    <label for="ape_materno">Apellido Materno</label>
                    <input type="text" id="ape_materno" name="ape_materno">
                </div>
                <div class="form-group">
                    <label for="tipo_doc">Tipo de Documento</label>
                    <select id="tipo_doc" name="tipo_doc">
                        <option value="DNI">DNI</option>
                        <option value="Carnet Universitario">Carnet de Extranjeria</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="num_doc">Número de Documento</label>
                    <input type="text" id="num_doc" name="num_doc" maxlength="15">
                </div>
                <div class="form-group">
                    <label for="celular">Celular</label>
                    <input type="text" id="celular" name="celular" maxlength="9">
                </div>
                <div class="form-group">
                    <label for="telefono_fijo">Teléfono Fijo</label>
                    <input type="text" id="telefono_fijo" name="telefono_fijo" maxlength="9">
                </div>

                <div class="seccion-titulo" style="margin-top: 10px;"><strong>Domicilio / Notificación</strong></div>
                <div class="form-group">
                    <label for="tipo_via">Tipo de Vía</label>
                    <select id="tipo_via" name="tipo_via">
                        <option value="Av.">Avenida</option>
                        <option value="Calle">Calle</option>
                        <option value="Urb.">Urbanización</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="nombre_via">Nombre de Vía / N° / Mz / Lt</label>
                    <input type="text" id="nombre_via" name="nombre_via">
                </div>

                <div class="form-group full-width">
                    <label for="referencia">Referencia de ubicación</label>
                    <input type="text" id="referencia" name="referencia">
                </div>

            </div>
            <div id="seccion_ubicacion" class="form-grid seccion-dinamica" style="display: none;">
                <div class="seccion-titulo" style="margin-top: 10px;"><strong>Ubicación Geográfica</strong></div>

                <div class="form-group">
                    <label for="departamento">Departamento</label>
                    <select id="departamento" name="departamento" onchange="cargarProvincias(this.value)">
                        <option value="" disabled selected>-- Seleccione Departamento --</option>
                        <?php foreach ($departamentos as $dep) { ?>
                            <option value="<?php echo $dep['id']; ?>"><?php echo $dep['name']; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="provincia">Provincia</label>
                    <select id="provincia" name="provincia" onchange="cargarDistritos(this.value)">
                        <option value="" disabled selected>-- Esperando... --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="distrito">Distrito (Obligatorio)</label>
                    <select id="distrito" name="id_distrito" required>
                        <option value="" disabled selected>-- Esperando... --</option>
                    </select>
                </div>
            </div>
            <div id="seccion_alumno" class="form-grid seccion-dinamica">
                <div class="seccion-titulo"><strong>Datos Académicos (Alumno)</strong></div>
                <div class="form-group">
                    <label for="cod_universitario">Código Universitario (Será su usuario) *</label>
                    <input type="text" id="cod_universitario" name="cod_universitario">
                </div>
                <div class="form-group">
                    <label for="id_escuela_alumno">Escuela Profesional / Facultad *</label>
                    <select id="id_escuela_alumno" name="id_escuela">
                        <option value="" disabled selected>-- Seleccione su Escuela --</option>
                        <?php foreach ($escuelas as $esc) { ?>
                            <option value="<?php echo $esc['id_escuela']; ?>"><?php echo $esc['nombre_escuela']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="anio_ingreso">Año de Ingreso</label>
                    <input type="number" id="anio_ingreso" name="anio_ingreso" min="1950" max="2026">
                </div>
                <div class="form-group">
                    <label for="ciclo">Ciclo Actual</label>
                    <select id="ciclo" name="ciclo">
                        <option value="" disabled selected>-- Seleccione su Ciclo --</option>
                        <option value="1">I</option>
                        <option value="2">II</option>
                        <option value="3">III</option>
                        <option value="4">IV</option>
                        <option value="5">V</option>
                        <option value="6">VI</option>
                        <option value="7">VII</option>
                        <option value="8">VIII</option>
                        <option value="9">IX</option>
                        <option value="10">X</option>
                        <option value="11">XI</option>
                        <option value="12">XII</option>
                    </select>
                </div>
            </div>

            <div id="seccion_personal" class="form-grid seccion-dinamica">
                <div class="seccion-titulo"><strong>Datos Laborales (Personal / Docente)</strong></div>
                <div class="form-group">
                    <label for="cod_admin">Código de Trabajador (Será su usuario) *</label>
                    <input type="text" id="cod_admin" name="cod_admin">
                </div>
                <div class="form-group">
                    <label for="cargo">Cargo / Especialidad</label>
                    <input type="text" id="cargo" name="cargo">
                </div>
                <div class="form-group">
                    <label for="condicion">Condición / Modalidad</label>
                    <select id="condicion" name="condicion">
                        <option value="" disabled selected>-- Seleccione su Condición --</option>
                        <option value="Nombrado">Nombrado</option>
                        <option value="Contratado">Contratado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría / Nivel</label>
                    <input type="text" id="categoria" name="categoria">
                </div>
            </div>

            <div id="seccion_egresado" class="form-grid seccion-dinamica">
                <div class="seccion-titulo"><strong>Datos Académicos (Egresado)</strong></div>
                <div class="form-group">
                    <label for="cod_egresado">Código Universitario (Será su usuario) *</label>
                    <input type="text" id="cod_egresado" name="cod_egresado">
                </div>
                <div class="form-group">
                    <label for="id_escuela_egresado">Escuela Profesional / Facultad *</label>
                    <select id="id_escuela_egresado" name="id_escuela_egresado">
                        <option value="" disabled selected>-- Seleccione su Escuela --</option>
                        <?php foreach ($escuelas as $esc) { ?>
                            <option value="<?php echo $esc['id_escuela']; ?>"><?php echo $esc['nombre_escuela']; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="anio_ingreso_egreso">Año de Ingreso</label>
                    <input type="number" id="anio_ingreso_egreso" name="anio_ingreso_egreso" min="1950" max="2026">
                </div>
                <div class="form-group">
                    <label for="anio_egreso">Año de Egreso</label>
                    <input type="number" id="anio_egreso" name="anio_egreso" min="1950" max="2026">
                </div>
            </div>

            <div id="seccion_institucion" class="form-grid seccion-dinamica">
                <div class="seccion-titulo"><strong>Datos de la Entidad (Persona Jurídica)</strong></div>
                <div class="form-group">
                    <label for="ruc">RUC (Será su usuario) *</label>
                    <input type="text" id="ruc" name="ruc" maxlength="11">
                </div>
                <div class="form-group">
                    <label for="razon_social">Razón Social *</label>
                    <input type="text" id="razon_social" name="razon_social">
                </div>
                <div class="form-group full-width">
                    <label for="direccion_inst">Dirección Fiscal *</label>
                    <input type="text" id="direccion_inst" name="direccion_inst">
                </div>
            </div>

            <button type="submit" class="btn-submit">COMPLETAR REGISTRO</button>
        </form>

        <div class="register-link">
            ¿Ya tienes una cuenta? <br><br> <a href="index.php">Ir a Iniciar Sesión</a>
        </div>
    </div>

    <script>
        function mostrarCampos() {
            var tipo = document.getElementById("tipo_usuario").value;

            // 1. Ocultar todo primero
            document.getElementById("seccion_personales").style.display = "none";
            document.getElementById("seccion_alumno").style.display = "none";
            document.getElementById("seccion_personal").style.display = "none";
            document.getElementById("seccion_egresado").style.display = "none";
            document.getElementById("seccion_institucion").style.display = "none";
            document.getElementById("seccion_ubicacion").style.display = "none"; // <-- Agregado

            // 2. Mostrar según tipo
            if (tipo === "1") {
                document.getElementById("seccion_personales").style.display = "grid";
                document.getElementById("seccion_alumno").style.display = "grid";
                document.getElementById("seccion_ubicacion").style.display = "grid"; // <-- Mostrar Ubigeo
            }
            else if (tipo === "2") {
                document.getElementById("seccion_personales").style.display = "grid";
                document.getElementById("seccion_personal").style.display = "grid";
                document.getElementById("seccion_ubicacion").style.display = "grid"; // <-- Mostrar Ubigeo
            }
            else if (tipo === "3") {
                document.getElementById("seccion_personales").style.display = "grid";
                document.getElementById("seccion_egresado").style.display = "grid";
                document.getElementById("seccion_ubicacion").style.display = "grid"; // <-- Mostrar Ubigeo
            }
            else if (tipo === "4") {
                document.getElementById("seccion_ubicacion").style.display = "grid";
                document.getElementById("seccion_institucion").style.display = "grid";
                // <-- AHORA SÍ SE MUESTRA PARA INSTITUCIÓN
            }
        }


        function cargarProvincias(id_dep) {
            const formData = new FormData();
            // CORREGIDO: Cambiamos 'id_departamento' por 'department_id'
            formData.append('department_id', id_dep);

            fetch('get_provincias.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('provincia').innerHTML = html;
                    document.getElementById('distrito').innerHTML = "<option value='' disabled selected>-- Esperando... --</option>";
                });
        }

        function cargarDistritos(id_prov) {
            const formData = new FormData();
            // CORREGIDO: Cambiamos 'id_provincia' por 'province_id'
            formData.append('province_id', id_prov);

            fetch('get_distritos.php', { method: 'POST', body: formData })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('distrito').innerHTML = html;
                });
        }

    </script>
</body>

</html>