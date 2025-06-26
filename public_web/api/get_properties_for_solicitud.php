<?php
// Este archivo solo debe procesar la solicitud AJAX y devolver opciones HTML.
// Incluir la lógica compartida necesaria.
require_once __DIR__ . '/../../includes/db.php'; // Ruta desde 'public/api' a 'includes'
require_once __DIR__ . '/../../includes/properties_logic.php'; // Donde está getPropertiesBySolicitudId

header('Content-Type: text/html; charset=utf-8'); // Asegurar la cabecera correcta

$options = '<option value="">Seleccione una propiedad</option>';

// Verificar si se recibió el ID de solicitud y si es un GET
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['solicitud_id'])) {
    $solicitud_id = filter_var($_GET['solicitud_id'], FILTER_VALIDATE_INT);

    if ($solicitud_id === false) {
        // Manejar error de ID inválido
        $options = '<option value="">Error: ID de solicitud inválido</option>';
         error_log("Intento de obtener propiedades con ID de solicitud inválido: " . ($_GET['solicitud_id'] ?? ''));
    } else {
        // Obtener las propiedades usando la función de lógica compartida
        $propiedades = getPropertiesBySolicitudId($conn, $solicitud_id);

        if (!empty($propiedades)) {
            foreach ($propiedades as $row) {
                $options .= '<option value="' . htmlspecialchars($row['id']) . '">' .
                           'ID: ' . htmlspecialchars($row['id']) .
                           ' - Propietario: ' . htmlspecialchars($row['propietario']) .
                           ' - Edificio: ' . htmlspecialchars($row['edificio']) .
                           ' - Ciudad: ' . htmlspecialchars($row['ciudad']) .
                           '</option>';
            }
        } else {
             $options = '<option value="">No se encontraron propiedades para esta solicitud</option>';
        }
    }
} else {
    // Manejar error si no se recibió el ID de solicitud o no es GET
    $options = '<option value="">Error: Solicitud inválida</option>';
     error_log("Intento de acceder a obtener_propiedades.php sin GET o solicitud_id");
}

echo $options; // Devolver las opciones HTML al script AJAX

?>