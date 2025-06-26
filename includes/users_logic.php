<?php
// Incluir el archivo de conexión a la base de datos.
require_once __DIR__ . '/db.php';

$user_messages = []; // Array para almacenar mensajes de éxito o error para Usuarios

// --- Lógica para Agregar Usuario (Cliente/Propietario) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_usuario'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? ''; // 'cliente' o 'propietario'

    if (empty($nombre) || !in_array($tipo, ['cliente', 'propietario'])) {
        $user_messages[] = "<p style='color:red;'>Error: Datos de formulario inválidos o faltantes para agregar usuario.</p>";
    } else {
        try {
            // Verificar si el nombre de usuario ya existe
            $stmt_verificar = $conn->prepare("SELECT id FROM usuarios WHERE nombre = :nombre");
            $stmt_verificar->bindParam(':nombre', $nombre);
            $stmt_verificar->execute();

            if ($stmt_verificar->rowCount() > 0) {
                $user_messages[] = "<p style='color:red;'>Error: El nombre de usuario '" . htmlspecialchars($nombre) . "' ya existe.</p>";
            } else {
                $stmt = $conn->prepare("INSERT INTO usuarios (nombre, tipo) VALUES (:nombre, :tipo)");
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->execute();
                $user_messages[] = "<p>Usuario (" . htmlspecialchars($tipo) . ") agregado correctamente.</p>";
                // No redirigimos aquí, la vista lo hará si es necesario.
            }
        } catch(PDOException $e) {
            $user_messages[] = "Error al agregar usuario: " . $e->getMessage();
            error_log("Error al agregar usuario: " . $e->getMessage());
        }
    }
}

// --- Lógica para Eliminar Usuario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_usuario'])) {
    $eliminar_id = filter_var($_POST['eliminar_id'] ?? '', FILTER_VALIDATE_INT);

    if ($eliminar_id === false) {
        $user_messages[] = "<p style='color:red;'>Error: ID de usuario a eliminar inválido.</p>";
    } else {
        try {
            // Considerar eliminaciones en cascada si aplica (propiedades, solicitudes)
            // Si la BD no lo hace automáticamente, necesitarías eliminar primero los registros relacionados.
            // El código de propietarios.php ya incluye la eliminación de propiedades asociadas.
            // Podríamos incorporar esa lógica aquí para eliminar propiedades y solicitudes antes de eliminar al usuario.

            // Eliminar propiedades asociadas a este usuario (si es propietario)
            $stmt_del_prop = $conn->prepare("DELETE FROM propiedades WHERE usuario_id = :id");
            $stmt_del_prop->bindParam(':id', $eliminar_id);
            $stmt_del_prop->execute(); // No verificamos rowCount aquí, ya que un cliente no tendría propiedades.

            // Eliminar solicitudes asociadas a este usuario (si es cliente)
             $stmt_del_sol = $conn->prepare("DELETE FROM solicitudes WHERE usuario_id = :id");
             $stmt_del_sol->bindParam(':id', $eliminar_id);
             $stmt_del_sol->execute(); // No verificamos rowCount aquí, ya que un propietario no tendría solicitudes de cliente.

            // Luego eliminar al usuario
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $eliminar_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user_messages[] = "<p>Usuario eliminado correctamente.</p>";
            } else {
                $user_messages[] = "<p style='color:orange;'>Advertencia: No se encontró el usuario con el ID especificado para eliminar.</p>";
            }

        } catch (PDOException $e) {
             // Podría ocurrir un error si hay otras dependencias (ej: relaciones)
             $user_messages[] = "Error al eliminar usuario: " . $e->getMessage();
             error_log("Error al eliminar usuario: " . $e->getMessage());
        }
    }
}

// --- Lógica para Actualizar Usuario ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_usuario'])) {
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nombre'] ?? '');
    $tipo = $_POST['tipo'] ?? ''; // 'cliente' o 'propietario'

    if ($id === false || empty($nombre) || !in_array($tipo, ['cliente', 'propietario'])) {
        $user_messages[] = "<p style='color:red;'>Error: Datos para actualizar usuario inválidos o faltantes.</p>";
    } else {
        try {
            // Verificar si el nuevo nombre ya existe (excepto para el usuario actual)
            $stmt_verificar = $conn->prepare("SELECT id FROM usuarios WHERE nombre = :nombre AND id != :id");
            $stmt_verificar->bindParam(':nombre', $nombre);
            $stmt_verificar->bindParam(':id', $id);
            $stmt_verificar->execute();

            if ($stmt_verificar->rowCount() > 0) {
                $user_messages[] = "<p style='color:red;'>Error: El nombre de usuario '" . htmlspecialchars($nombre) . "' ya existe.</p>";
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, tipo = :tipo WHERE id = :id");
                $stmt->bindParam(':nombre', $nombre);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $user_messages[] = "<p>Usuario actualizado correctamente.</p>";
                } else {
                    $user_messages[] = "<p style='color:orange;'>Advertencia: No se realizó la actualización del usuario (quizás el nombre o tipo no cambiaron).</p>";
                }
            }
        } catch(PDOException $e) {
             $user_messages[] = "Error al actualizar usuario: " . $e->getMessage();
             error_log("Error al actualizar usuario: " . $e->getMessage());
        }
    }
}


// --- Funciones para Obtener Datos (para ser usadas en las vistas) ---

/**
 * Obtiene la lista de todos los usuarios.
 */
function getAllUsers($conn) {
    try {
        $stmt = $conn->prepare("SELECT id, nombre, tipo FROM usuarios ORDER BY tipo ASC, nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener todos los usuarios: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene un usuario por su ID.
 */
function getUserById($conn, $id) {
     $id = filter_var($id, FILTER_VALIDATE_INT);
     if ($id === false) return null;
    try {
        $stmt = $conn->prepare("SELECT id, nombre, tipo FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener usuario por ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene la lista de usuarios de un tipo específico ('cliente' o 'propietario').
 */
function getUsersByType($conn, $type) {
    if (!in_array($type, ['cliente', 'propietario'])) return [];
    try {
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE tipo = :tipo ORDER BY nombre ASC");
        $stmt->bindParam(':tipo', $type);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // error_log("Error al obtener usuarios por tipo " . $type . ": " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las propiedades asociadas a un propietario (usuario_id).
 */
function getPropertiesByOwnerId($conn, $ownerId) {
     $ownerId = filter_var($ownerId, FILTER_VALIDATE_INT);
     if ($ownerId === false) return [];
     try {
         $stmt = $conn->prepare("SELECT p.id, e.nombre as edificio, c.nombre as ciudad
                                FROM propiedades p
                                JOIN edificios e ON p.edificio_id = e.id
                                JOIN ciudades c ON p.ciudad_id = c.id
                                WHERE p.usuario_id = :usuario_id");
         $stmt->bindParam(':usuario_id', $ownerId);
         $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
     } catch(PDOException $e) {
         // error_log("Error al obtener propiedades por propietario: " . $e->getMessage());
         return [];
     }
}

/**
 * Obtiene las solicitudes para un edificio/ciudad específico.
 * Usado en propietarios.php para listar clientes interesados en las propiedades del propietario.
 */
function getSolicitudesByEdificioCiudad($conn, $edificioNombre, $ciudadNombre) {
     try {
          $stmt = $conn->prepare("SELECT u.nombre as cliente
                                 FROM solicitudes s
                                 JOIN usuarios u ON s.usuario_id = u.id
                                 JOIN edificios e ON s.edificio_id = e.id
                                 JOIN ciudades c ON s.ciudad_id = c.id
                                 WHERE e.nombre = :nombre_edificio AND c.nombre = :nombre_ciudad");
          $stmt->bindParam(':nombre_edificio', $edificioNombre);
          $stmt->bindParam(':nombre_ciudad', $ciudadNombre);
          $stmt->execute();
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
     } catch(PDOException $e) {
          // error_log("Error al obtener solicitudes por edificio/ciudad: " . $e->getMessage());
          return [];
     }
}


// Nota: La conexión $conn y el array $user_messages ahora están disponibles
// para las páginas que incluyan este script.

?>