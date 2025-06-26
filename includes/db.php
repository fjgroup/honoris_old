<?php
require_once 'db_config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$nombre_base_de_datos", $usuario, $contrasena);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conexi贸n exitosa"; // Esto es solo para pruebas, se debe comentar o eliminar
} catch(PDOException $e) {
    echo "Error de conexi贸n: " . $e->getMessage();
}
?>
