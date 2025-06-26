<?php
// Incluir el archivo de conexión a la base de datos.
require_once __DIR__ . '/db.php';

$property_messages = []; // Array para almacenar mensajes de éxito o error para Propiedades

// --- Lógica para Agregar Propiedad ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_propiedad'])) {
    $usuario_id = filter_var($_POST['propietario_id'] ?? '', FILTER_VALIDATE_INT);
    $edificio_id = filter_var($_POST['edificio_id'] ?? '', FILTER_VALIDATE_INT);
    $ciudad_id = filter_var($_POST['ciudad_id'] ?? '', FILTER_VALIDATE_INT);

    if ($usuario_id === false || $edificio_id === false || $ciudad_id === false || empty($_POST['propietario_id']) || empty($_POST['edificio_id']) || empty($_POST['ciudad_id'])) {
        $property_messages[] = "<p style='color:red;'>Error: Datos de formulario inválidos o faltantes para agregar propiedad.</p>";
    } else {
        try {
            // Opcional: Verificar si ya existe esta combinación propiedad-edificio-ciudad para el propietario?
            // Depende de si una propiedad se define únicamente por propietario+edificio+ciudad.
            // Tu código original no lo hacía, así que no lo incluiré por ahora.

            $stmt = $conn->prepare("INSERT INTO propiedades (usuario_id, edificio_id, ciudad_id) VALUES (:usuario_id, :edificio_id, :ciudad_id)");
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':edificio_id', $edificio_id);
            $stmt->bindParam(':ciudad_id', $ciudad_id);
            $stmt->execute();
            $property_messages[] = "<p>Propiedad agregada correctamente.</p>";
            // Redirección handled by view
        } catch(PDOException $e) {
            $property_messages[] = "Error al agregar propiedad: " . $e->getMessage();
            error_log("Error al agregar propiedad: " . $e->getMessage());
        }
    }
}

// --- Lógica para Eliminar Propiedad ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_propiedad'])) {
    $eliminar_propiedad_id = filter_var($_POST['eliminar_propiedad_id'] ?? '', FILTER_VALIDATE_INT);

    if ($eliminar_propiedad_id === false) {
        $property_messages[] = "<p style='color:red;'>Error: ID de propiedad a eliminar inválido.</p>";
    } else {
        try {
            // Considerar si hay restricciones de clave externa (ej: relaciones)
            // Si existen, la eliminación fallará a menos que la BD esté configurada con ON DELETE CASCADE
            // o manejes primero la eliminación de relaciones asociadas a esta propiedad.

            // Eliminar relaciones asociadas a esta propiedad
            $stmt_del_rel = $conn->prepare("DELETE FROM relaciones WHERE propiedad_id = :id");
            $stmt_del_rel->bindParam(':id', $eliminar_propiedad_id);
            $stmt_del_rel->execute(); // No verificamos rowCount aquí.

            // Luego eliminar la propiedad
            $stmt = $conn->prepare("DELETE FROM propiedades WHERE id = :id");
            $stmt->bindParam(':id', $eliminar_propiedad_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $property_messages[] = "<p>Propiedad eliminada correctamente.</p>";
            } else {
                 $property_messages[] = "<p style='color:orange;'>Advertencia: No se encontró la propiedad con el ID especificado para eliminar.</p>";
            }

        } catch(PDOException $e) {
            // Error si hay otras dependencias
             $property_messages[] = "Error al eliminar propiedad: " . $e->getMessage();
             error_log("Error al eliminar propiedad: " . $e->getMessage());
        }
    }
}

// --- Funciones para Obtener Datos (para ser usadas en las vistas) ---

/**
 * Obtiene la lista de todas las propiedades con detalles de propietario, edificio y ciudad.
 */
function getAllProperties($conn) {
    try {
        $stmt = $conn->prepare("SELECT p.id, u.nombre as propietario, e.nombre as edificio, c.nombre as ciudad FROM propiedades p JOIN usuarios u ON p.usuario_id = u.id JOIN edificios e ON p.edificio_id = e.id JOIN ciudades c ON p.ciudad_id = c.id ORDER BY ciudad ASC, edificio ASC, propietario ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener todas las propiedades: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene propiedades basadas en el edificio y ciudad de una solicitud específica.
 * Usado para poblar el select de propiedades en la página de relaciones.
 */
function getPropertiesBySolicitudId($conn, $solicitudId) {
     $solicitudId = filter_var($solicitudId, FILTER_VALIDATE_INT);
     if ($solicitudId === false) return [];

     try {
         // Primero obtener el edificio_id y ciudad_id de la solicitud
         $stmt_sol = $conn->prepare("SELECT edificio_id, ciudad_id FROM solicitudes WHERE id = :solicitud_id");
         $stmt_sol->bindParam(':solicitud_id', $solicitudId);
         $stmt_sol->execute();
         $solicitud = $stmt_sol->fetch(PDO::FETCH_ASSOC);

         if (!$solicitud) {
             return []; // No se encontró la solicitud
         }

         // Luego obtener las propiedades que coinciden en edificio y ciudad
         $stmt_prop = $conn->prepare("SELECT p.id, u.nombre as propietario, e.nombre as edificio, c.nombre as ciudad
                                FROM propiedades p
                                JOIN usuarios u ON p.usuario_id = u.id
                                JOIN edificios e ON p.edificio_id = e.id
                                JOIN ciudades c ON p.ciudad_id = c.id
                                WHERE p.edificio_id = :edificio_id AND p.ciudad_id = :ciudad_id");
         $stmt_prop->bindParam(':edificio_id', $solicitud['edificio_id']);
         $stmt_prop->bindParam(':ciudad_id', $solicitud['ciudad_id']);
         $stmt_prop->execute();

         return $stmt_prop->fetchAll(PDO::FETCH_ASSOC);

     } catch(PDOException $e) {
         // error_log("Error al obtener propiedades por ID de solicitud: " . $e->getMessage());
         return [];
     }
}


// Nota: La conexión $conn y el array $property_messages ahora están disponibles
// para las páginas que incluyan este script.
// También necesitarás las funciones de users_logic.php para obtener la lista de propietarios
// para el formulario de agregar propiedad.

?>