<?php
session_start();

if (!isset($_SESSION["auth"]) || $_SESSION["auth"] != "1" || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$id_usuario = $_SESSION["id_usuario"];

if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $archivo = $_FILES['foto'];
    $nombre_original = $archivo['name'];
    $tipo_archivo = $archivo['type'];
    $temporal = $archivo['tmp_name'];
    $tamano = $archivo['size'];
    
    // 1. Validar la extensión del archivo
    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($ext, $extensiones_permitidas)) {
        header("Location: perfil_foto.php?error=formato");
        exit();
    }
    
    // 2. Validar tamaño (Máximo 2 Megabytes = 2 * 1024 * 1024 bytes)
    if ($tamano > 2097152) {
        header("Location: perfil_foto.php?error=tamano");
        exit();
    }
    
    // 3. Crear la carpeta "fotos" si no existe en el servidor
    $carpeta_destino = "fotos/";
    if (!file_exists($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }
    
    // 4. Limpiar cualquier foto anterior con otra extensión para que no deje residuos
    foreach ($extensiones_permitidas as $e) {
        $foto_vieja = $carpeta_destino . "usuario_" . $id_usuario . "." . $e;
        if (file_exists($foto_vieja)) {
            unlink($foto_vieja);
        }
    }
    
    // 5. Definir la nueva ruta única
    $nuevo_nombre = "usuario_" . $id_usuario . "." . $ext;
    $ruta_final = $carpeta_destino . $nuevo_nombre;
    
    // 6. Mover el archivo desde la ubicación temporal del servidor a la carpeta final
    if (move_uploaded_file($temporal, $ruta_final)) {
        header("Location: perfil_foto.php?status=success");
        exit();
    } else {
        header("Location: perfil_foto.php?error=subida");
        exit();
    }
} else {
    header("Location: perfil_foto.php?error=subida");
    exit();
}
?>