<?php
// Incluir el archivo de conexión a la base de datos.
require_once __DIR__ . '/db.php';

$relation_messages = []; // Array para almacenar mensajes de éxito o error para Relaciones

// --- Lógica para Agregar Relación ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_relacion'])) {
    $solicitud_id = filter_var($_POST['solicitud_id'] ?? '', FILTER_VALIDATE_INT);
    $propiedad_id = filter_var($_POST['propiedad_id'] ?? '', FILTER_VALIDATE_INT);

    if ($solicitud_id === false || $propiedad_id === false || empty($_POST['solicitud_id']) || empty($_POST['propiedad_id'])) {
        $relation_messages[] = "<p style='color: red;'>Por favor, seleccione una solicitud y una propiedad.</p>";
    } else {
        try {
             // Opcional: Verificar si la relación ya existe para evitar duplicados
             $stmt_check = $conn->prepare("SELECT COUNT(*) FROM relaciones WHERE solicitud_id = :solicitud_id AND propiedad_id = :propiedad_id");
             $stmt_check->bindParam(':solicitud_id', $solicitud_id);
             $stmt_check->bindParam(':propiedad_id', $propiedad_id);
             $stmt_check->execute();
             $count = $stmt_check->fetchColumn();

             if ($count > 0) {
                  $relation_messages[] = "<p style='color: red;'>Error: Esta relación entre solicitud y propiedad ya existe.</p>";
             } else {
                 $stmt = $conn->prepare("INSERT INTO relaciones (solicitud_id, propiedad_id) VALUES (:solicitud_id, :propiedad_id)");
                 $stmt->bindParam(':solicitud_id', $solicitud_id);
                 $stmt->bindParam(':propiedad_id', $propiedad_id);
                 $stmt->execute();
                 $relation_messages[] = "<p style='color: green;'>Relación agregada correctamente.</p>";
                 // Redirección handled by view
             }
        } catch (PDOException $e) {
            $relation_messages[] = "<p style='color: red;'>Error al agregar relación: " . $e->getMessage() . "</p>";
            error_log("Error al agregar relación: " . $e->getMessage());
        }
    }
}

// --- Lógica para Eliminar Relación ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_relacion'])) {
    $eliminar_relacion_id = filter_var($_POST['eliminar_relacion_id'] ?? '', FILTER_VALIDATE_INT);

    if ($eliminar_relacion_id === false) {
        $relation_messages[] = "<p style='color: red;'>Error: ID de relación a eliminar inválido.</p>";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM relaciones WHERE id = :id");
            $stmt->bindParam(':id', $eliminar_relacion_id);
            $stmt->execute();
             if ($stmt->rowCount() > 0) {
                 $relation_messages[] = "<p style='color: green;'>Relación eliminada correctamente.</p>";
             } else {
                 $relation_messages[] = "<p style='color: orange;'>Advertencia: No se encontró la relación con el ID especificado para eliminar.</p>";
             }
            // Redirección handled by view
        } catch (PDOException $e) {
            $relation_messages[] = "<p style='color: red;'>Error al eliminar relación: " . $e->getMessage() . "</p>";
            error_log("Error al eliminar relación: " . $e->getMessage());
        }
    }
}

// --- Funciones para Obtener Datos (para ser usadas en las vistas) ---

/**
 * Obtiene la lista de todas las relaciones con detalles de solicitud y propiedad.
 * Incluye la lógica de filtrado por nombre de propietario si se aplica.
 */
function getAllRelations($conn, $filter_propietario = false) {
    try {
        $sql = "SELECT r.id as relacion_id, s.id as solicitud_id, u_cliente.nombre as cliente, e_solicitado.nombre as edificio_solicitado, c_solicitada.nombre as ciudad_solicitada, p.id as propiedad_id, u_propietario.nombre as propietario, e_propiedad.nombre as edificio_propiedad, c_propiedad.nombre as ciudad_propiedad
                FROM relaciones r
                JOIN solicitudes s ON r.solicitud_id = s.id
                JOIN usuarios u_cliente ON s.usuario_id = u_cliente.id
                JOIN edificios e_solicitado ON s.edificio_id = e_solicitado.id
                JOIN ciudades c_solicitada ON s.ciudad_id = c_solicitada.id
                JOIN propiedades p ON r.propiedad_id = p.id
                JOIN usuarios u_propietario ON p.usuario_id = u_propietario.id
                JOIN edificios e_propiedad ON p.edificio_id = e_propiedad.id
                JOIN ciudades c_propiedad ON p.ciudad_id = c_propiedad.id";

        // Aplicar el filtro si se solicita
        if ($filter_propietario) {
             // Asegúrate de que esta lógica de filtro coincida con tu necesidad ('%*F')
            $sql .= " WHERE u_propietario.nombre NOT LIKE '%*F'";
        }

        $sql .= " ORDER BY ciudad_solicitada ASC, cliente DESC, edificio_propiedad ASC"; // Mantener el orden original

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // error_log("Error al obtener relaciones: " . $e->getMessage());
        return [];
    }
}

// Nota: La conexión $conn y el array $relation_messages ahora están disponibles
// para las páginas que incluyan este script.
// Necesitarás obtener las solicitudes y propiedades disponibles desde otras lógicas compartidas.

?>