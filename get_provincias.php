<?php
require 'conexion.php';

if (isset($_POST['department_id'])) {
    $dep_id = $_POST['department_id'];

    // Usamos $cn (tu variable de conexión) en lugar de $pdo
    $query = "SELECT id, name FROM ubigeo_peru_provinces WHERE department_id = '$dep_id' ORDER BY name ASC";
    $result = $cn->query($query);

    echo '<option value="" disabled selected>-- Seleccione Provincia --</option>';
    
    if ($result) {
        while ($prov = $result->fetch_assoc()) {
            echo '<option value="' . $prov['id'] . '">' . $prov['name'] . '</option>';
        }
    }
}
?>