<?php
// Incluir el archivo de conexión a la base de datos.
require_once __DIR__ . '/db.php';

$location_messages = []; // Array para almacenar mensajes de éxito o error para Ubicaciones

// --- Lógica para Agregar Ciudad ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_ciudad'])) {
    $nombre_ciudad = trim($_POST['nombre_ciudad'] ?? '');

    if (empty($nombre_ciudad)) {
        $location_messages[] = "<p style='color:red;'>Error: El nombre de la ciudad no puede estar vacío.</p>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO ciudades (nombre) VALUES (:nombre)");
            $stmt->bindParam(':nombre', $nombre_ciudad);
            $stmt->execute();
            $location_messages[] = "<p>Ciudad agregada correctamente.</p>";
        } catch(PDOException $e) {
            // Error si la ciudad ya existe (si el campo nombre tiene un índice UNIQUE)
            if ($e->getCode() == '23000') { // Código de error para violación de integridad (duplicado)
                 $location_messages[] = "<p style='color:red;'>Error: La ciudad '" . htmlspecialchars($nombre_ciudad) . "' ya existe.</p>";
            } else {
                 $location_messages[] = "Error al agregar ciudad: " . $e->getMessage();
                 error_log("Error al agregar ciudad: " . $e->getMessage());
            }
        }
    }
}

// --- Lógica para Agregar Edificio ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_edificio'])) {
    $nombre_edificio = trim($_POST['nombre_edificio'] ?? '');

    if (empty($nombre_edificio)) {
        $location_messages[] = "<p style='color:red;'>Error: El nombre del edificio no puede estar vacío.</p>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO edificios (nombre) VALUES (:nombre)");
            $stmt->bindParam(':nombre', $nombre_edificio);
            $stmt->execute();
            $location_messages[] = "<p>Edificio agregado correctamente.</p>";
        } catch(PDOException $e) {
            // Error si el edificio ya existe (si el campo nombre tiene un índice UNIQUE)
            if ($e->getCode() == '23000') { // Código de error para violación de integridad (duplicado)
                 $location_messages[] = "<p style='color:red;'>Error: El edificio '" . htmlspecialchars($nombre_edificio) . "' ya existe.</p>";
            } else {
                 $location_messages[] = "Error al agregar edificio: " . $e->getMessage();
                 error_log("Error al agregar edificio: " . $e->getMessage());
            }
        }
    }
}

// --- Lógica para Eliminar Ciudad ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_ciudad'])) {
    $eliminar_ciudad_id = filter_var($_POST['eliminar_ciudad_id'] ?? '', FILTER_VALIDATE_INT);

    if ($eliminar_ciudad_id === false) {
        $location_messages[] = "<p style='color:red;'>Error: ID de ciudad a eliminar inválido.</p>";
    } else {
        try {
            // Considerar si hay restricciones de clave externa antes de eliminar
            // Por ejemplo, si hay edificios o solicitudes/propiedades asociadas a esta ciudad.
            // Si existen, la eliminación fallará a menos que la BD esté configurada con ON DELETE CASCADE
            // o manejes primero la eliminación de elementos relacionados.
            $stmt = $conn->prepare("DELETE FROM ciudades WHERE id = :id");
            $stmt->bindParam(':id', $eliminar_ciudad_id);
            $stmt->execute();
            // Verificar si se eliminó alguna fila
            if ($stmt->rowCount() > 0) {
                 $location_messages[] = "<p>Ciudad eliminada correctamente.</p>";
            } else {
                 $location_messages[] = "<p style='color:orange;'>Advertencia: No se encontró la ciudad con el ID especificado para eliminar.</p>";
            }

        } catch (PDOException $e) {
             // Error si hay dependencias de clave externa
             if ($e->getCode() == '23000') {
                  $location_messages[] = "<p style='color:red;'>Error: No se puede eliminar la ciudad porque tiene edificios, solicitudes o propiedades asociadas.</p>";
             } else {
                  $location_messages[] = "Error al eliminar ciudad: " . $e->getMessage();
                  error_log("Error al eliminar ciudad: " . $e->getMessage());
             }
        }
    }
}

// --- Lógica para Eliminar Edificio ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_edificio'])) {
    $eliminar_edificio_id = filter_var($_POST['eliminar_edificio_id'] ?? '', FILTER_VALIDATE_INT);

    if ($eliminar_edificio_id === false) {
        $location_messages[] = "<p style='color:red;'>Error: ID de edificio a eliminar inválido.</p>";
    } else {
        try {
             // Considerar si hay restricciones de clave externa
             $stmt = $conn->prepare("DELETE FROM edificios WHERE id = :id");
             $stmt->bindParam(':id', $eliminar_edificio_id);
             $stmt->execute();
             if ($stmt->rowCount() > 0) {
                 $location_messages[] = "<p>Edificio eliminado correctamente.</p>";
             } else {
                 $location_messages[] = "<p style='color:orange;'>Advertencia: No se encontró el edificio con el ID especificado para eliminar.</p>";
             }
        } catch (PDOException $e) {
             if ($e->getCode() == '23000') {
                 $location_messages[] = "<p style='color:red;'>Error: No se puede eliminar el edificio porque tiene solicitudes o propiedades asociadas.</p>";
            } else {
                 $location_messages[] = "Error al eliminar edificio: " . $e->getMessage();
                 error_log("Error al eliminar edificio: " . $e->getMessage());
            }
        }
    }
}

// --- Lógica para Actualizar Ciudad ---
// Esta lógica se activa al enviar el formulario de edición de ciudad por POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_ciudad'])) {
    $id = filter_var($_POST['ciudad_id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre_ciudad'] ?? '');

    if ($id === false || empty($nombre)) {
        $location_messages[] = "<p style='color:red;'>Error: Datos para actualizar ciudad inválidos o faltantes.</p>";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE ciudades SET nombre = :nombre WHERE id = :id");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $location_messages[] = "<p>Ciudad actualizada correctamente.</p>";
            } else {
                $location_messages[] = "<p style='color:orange;'>Advertencia: No se realizó la actualización de la ciudad (quizás el nombre no cambió).</p>";
            }
        } catch(PDOException $e) {
             if ($e->getCode() == '23000') { // Error de duplicado
                  $location_messages[] = "<p style='color:red;'>Error: Ya existe otra ciudad con el nombre '" . htmlspecialchars($nombre) . "'.</p>";
             } else {
                 $location_messages[] = "Error al actualizar ciudad: " . $e->getMessage();
                 error_log("Error al actualizar ciudad: " . $e->getMessage());
             }
        }
    }
}

// --- Lógica para Actualizar Edificio ---
// Esta lógica se activa al enviar el formulario de edición de edificio por POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_edificio'])) {
    $id = filter_var($_POST['edificio_id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre_edificio'] ?? '');

    if ($id === false || empty($nombre)) {
        $location_messages[] = "<p style='color:red;'>Error: Datos para actualizar edificio inválidos o faltantes.</p>";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE edificios SET nombre = :nombre WHERE id = :id");
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
             if ($stmt->rowCount() > 0) {
                 $location_messages[] = "<p>Edificio actualizado correctamente.</p>";
             } else {
                 $location_messages[] = "<p style='color:orange;'>Advertencia: No se realizó la actualización del edificio (quizás el nombre no cambió).</p>";
             }
        } catch(PDOException $e) {
             if ($e->getCode() == '23000') { // Error de duplicado
                  $location_messages[] = "<p style='color:red;'>Error: Ya existe otro edificio con el nombre '" . htmlspecialchars($nombre) . "'.</p>";
             } else {
                  $location_messages[] = "Error al actualizar edificio: " . $e->getMessage();
                  error_log("Error al actualizar edificio: " . $e->getMessage());
             }
        }
    }
}


// --- Funciones para Obtener Datos (para ser usadas en las vistas) ---

/**
 * Obtiene la lista de todas las ciudades.
 */
function getAllCiudades($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM ciudades ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener ciudades: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene una ciudad por su ID.
 */
function getCiudadById($conn, $id) {
     $id = filter_var($id, FILTER_VALIDATE_INT);
     if ($id === false) return null;
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM ciudades WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener ciudad por ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la lista de todos los edificios.
 */
function getAllEdificios($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM edificios ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener edificios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene un edificio por su ID.
 */
function getEdificioById($conn, $id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false) return null;
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM edificios WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener edificio por ID: " . $e->getMessage());
        return null;
    }
}

// Nota: La conexión $conn y el array $location_messages ahora están disponibles
// para las páginas que incluyan este script.

?>