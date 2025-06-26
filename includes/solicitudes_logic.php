<?php
// Incluir el archivo de conexión a la base de datos.
// La ruta es relativa desde la carpeta 'includes' a 'db.php' (que también estará en 'includes').
require_once __DIR__ . '/db.php';

$messages = []; // Array para almacenar mensajes de éxito o error

// --- Lógica para Agregar Solicitud ---
// Esta lógica se mantiene igual para admin y (posiblemente) público si permite agregar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_solicitud'])) {
    // Validar y sanear inputs (básico, considera añadir más validación si es necesario)
    $usuario_id = filter_var($_POST['cliente_id'] ?? '', FILTER_VALIDATE_INT);
    $edificio_id = filter_var($_POST['edificio_id'] ?? '', FILTER_VALIDATE_INT);
    $ciudad_id = filter_var($_POST['ciudad_id'] ?? '', FILTER_VALIDATE_INT);

    if ($usuario_id === false || $edificio_id === false || $ciudad_id === false || empty($_POST['cliente_id']) || empty($_POST['edificio_id']) || empty($_POST['ciudad_id'])) {
        $messages[] = "<p style='color:red;'>Error: Datos de formulario inv\u00e1lidos o faltantes.</p>"; // Usamos \u00e1 para 'á'
    } else {
        try {
            // Verificar si ya existe una solicitud para el mismo edificio y ciudad
            $stmt_check = $conn->prepare("SELECT COUNT(*) FROM solicitudes WHERE usuario_id = :usuario_id AND edificio_id = :edificio_id AND ciudad_id = :ciudad_id");
            $stmt_check->bindParam(':usuario_id', $usuario_id);
            $stmt_check->bindParam(':edificio_id', $edificio_id);
            $stmt_check->bindParam(':ciudad_id', $ciudad_id);
            $stmt_check->execute();
            $count = $stmt_check->fetchColumn();

            if ($count > 0) {
                $messages[] = "<p style='color:red;'>Error: Ya existe una solicitud para este edificio en esta ciudad.</p>";
            } else {
                $stmt = $conn->prepare("INSERT INTO solicitudes (usuario_id, edificio_id, ciudad_id) VALUES (:usuario_id, :edificio_id, :ciudad_id)");
                $stmt->bindParam(':usuario_id', $usuario_id);
                $stmt->bindParam(':edificio_id', $edificio_id);
                $stmt->bindParam(':ciudad_id', $ciudad_id);
                $stmt->execute();
                $messages[] = "<p>Solicitud agregada correctamente.</p>";

                 // Redirigir para evitar el reenvío del formulario al refrescar
                 // Esto solo debe ocurrir si el procesamiento fue exitoso Y es una página admin
                 // La redirecci\u00f3n debe ser manejada por la p\u00e1gina que incluye este script si es necesario.
                 // Opcionalmente, puedes agregar una bandera para que la p\u00e1gina de vista sepa si debe redirigir.
            }
        } catch(PDOException $e) {
            $messages[] = "Error al agregar solicitud: " . $e->getMessage();
        }
    }
}

// --- Lógica para Eliminar Solicitud ---
// Esta lógica SOLO debe ser accesible en la versión de administración
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_solicitud'])) {
    // Validar y sanear inputs
    $eliminar_solicitud_id = filter_var($_POST['eliminar_solicitud_id'] ?? '', FILTER_VALIDATE_INT);

     if ($eliminar_solicitud_id === false) {
         $messages[] = "<p style='color:red;'>Error: ID de solicitud a eliminar inv\u00e1lido.</p>";
     } else {
         try {
             $stmt = $conn->prepare("DELETE FROM solicitudes WHERE id = :id");
             $stmt->bindParam(':id', $eliminar_solicitud_id);
             $stmt->execute();
             $messages[] = "<p>Solicitud eliminada correctamente.</p>";

             // Redirigir para evitar el reenvío del formulario al refrescar
             // Esto solo debe ocurrir si el procesamiento fue exitoso Y es una página admin
         } catch(PDOException $e) {
             $messages[] = "Error al eliminar solicitud: " . $e->getMessage();
         }
     }
}

// --- Funciones para Obtener Datos (para ser usadas en las vistas) ---

/**
 * Obtiene la lista de usuarios de tipo 'cliente'.
 */
function getClientes($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE tipo = 'cliente' ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // En lugar de hacer echo aquí, podrías agregar a un array de errores o loguear
        // echo "Error al obtener clientes: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene la lista de edificios.
 */
function getEdificios($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM edificios ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // echo "Error al obtener edificios: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene la lista de ciudades.
 */
function getCiudades($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM ciudades ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // echo "Error al obtener ciudades: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene la lista de todas las solicitudes con detalles de cliente, edificio y ciudad.
 */
function getAllSolicitudes($conn) {
    try {
        $stmt = $conn->prepare("SELECT s.id, u.nombre as cliente, e.nombre as edificio, c.nombre as ciudad FROM solicitudes s JOIN usuarios u ON s.usuario_id = u.id JOIN edificios e ON s.edificio_id = e.id JOIN ciudades c ON s.ciudad_id = c.id ORDER BY ciudad ASC, edificio ASC, cliente ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // echo "Error al obtener solicitudes: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene la lista de solicitudes que NO están en la tabla 'relaciones'.
 * (Adaptado de solicitudes_honoris.php)
 */
function getUnrelatedSolicitudes($conn) {
    try {
         $stmt = $conn->prepare("SELECT s.id, u.nombre as cliente, e.nombre as edificio, c.nombre as ciudad FROM solicitudes s JOIN usuarios u ON s.usuario_id = u.id JOIN edificios e ON s.edificio_id = e.id JOIN ciudades c ON s.ciudad_id = c.id WHERE s.id NOT IN (SELECT solicitud_id FROM relaciones) ORDER BY ciudad ASC, edificio ASC, cliente ASC");
         $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // echo "Error al obtener solicitudes no relacionadas: " . $e->getMessage();
        return [];
    }
}

// Nota: La conexión $conn ahora está disponible para las funciones aquí definidas.
// Las páginas que incluyan este archivo tendrán $conn y las funciones disponibles.

?>