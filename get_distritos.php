<?php
require 'conexion.php';

if (isset($_POST['province_id'])) {
    $prov_id = $_POST['province_id'];

    // Usamos $cn (tu variable de conexión) en lugar de $pdo
    $query = "SELECT id, name FROM ubigeo_peru_districts WHERE province_id = '$prov_id' ORDER BY name ASC";
    $result = $cn->query($query);

    echo '<option value="" disabled selected>-- Seleccione Distrito --</option>';
    
    if ($result) {
        while ($dist = $result->fetch_assoc()) {
            echo '<option value="' . $dist['id'] . '">' . $dist['name'] . '</option>';
        }
    }
}
?>