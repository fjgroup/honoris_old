<?php
// Incluir la lógica compartida para usuarios y la conexión a la BD.
// La ruta es relativa desde 'admin' a 'includes'.
require_once __DIR__ . '/../includes/users_logic.php';

// Si se procesó una acción POST (agregar/eliminar/actualizar) en users_logic.php
// y generó mensajes de éxito, redirigir para evitar reenvío de formulario.
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($user_messages)) {
    $success_messages_exist = false;
    foreach($user_messages as $msg) {
        if (strpos($msg, "<p style='color:red;'>") === false && strpos($msg, "<p style='color:orange;'>") === false) {
            $success_messages_exist = true;
            break;
        }
    }
     if ($success_messages_exist) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
     }
}

// Obtener datos para la lista y el formulario de edición ANTES de renderizar el HTML
$all_users = getAllUsers($conn);

// Obtener datos del usuario a editar si el parámetro 'id' está en la URL
$user_to_edit = null;
if (isset($_GET['id'])) {
     $user_to_edit = getUserById($conn, $_GET['id']);
}

// Incluir el nuevo menú unificado.
// La ruta es relativa desde 'admin' a 'includes'.
$base_url = '/honoris/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Clientes/Usuarios (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Puedes añadir estilos específicos o usar clases de Tailwind */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        input[type="text"], select { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .messages p { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
        .messages p[style*='color:red'] { background-color: #fdd; border: 1px solid #fbc; }
        .messages p[style*='color:orange'] { background-color: #ffe; border: 1px solid #ffc; }

    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Clientes/Usuarios (Admin)</h2>

            <div class="messages">
                <?php
                // Mostrar mensajes de las operaciones (desde users_logic.php)
                foreach ($user_messages as $msg) {
                    echo $msg;
                }
                ?>
            </div>

            <?php if ($user_to_edit): ?>
                <h3 class="mt-8">Editar Usuario</h3>
                <form method="post" action="clientes.php">
                    <input type="hidden" name="id" value="<?php echo $user_to_edit['id']; ?>">
                    <div>
                        <label for="edit_nombre">Nombre:</label>
                        <input type="text" name="nombre" id="edit_nombre" value="<?php echo htmlspecialchars($user_to_edit['nombre']); ?>" required>
                    </div>
                    <div class="mt-4">
                        <label for="edit_tipo">Tipo:</label>
                        <select name="tipo" id="edit_tipo" required>
                            <option value="cliente" <?php if ($user_to_edit['tipo'] == 'cliente') echo 'selected'; ?>>Cliente</option>
                            <option value="propietario" <?php if ($user_to_edit['tipo'] == 'propietario') echo 'selected'; ?>>Propietario</option>
                        </select>
                    </div>
                    <div class="mt-6">
                        <input type="submit" name="actualizar_usuario" value="Actualizar Usuario">
                    </div>
                </form>
            <?php endif; ?>

             <?php if (!$user_to_edit): ?>
             <h3 class="mt-8">Agregar Cliente/Propietario</h3>
             <form method="post">
                 <div>
                     <label for="nombre">Nombre:</label>
                     <input type="text" name="nombre" id="nombre" value="" required>
                 </div>
                 <div class="mt-4">
                     <label for="tipo">Tipo:</label>
                     <select name="tipo" id="tipo" required>
                         <option value="cliente">Cliente</option>
                         <option value="propietario">Propietario</option>
                     </select>
                 </div>
                 <div class="mt-6">
                     <input type="submit" name="agregar_usuario" value="Agregar Usuario">
                 </div>
             </form>
             <?php endif; ?>


            <h3 class="mt-8">Lista de Clientes/Propietarios</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th colspan="2">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                            <td><a href="clientes.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline">Editar</a></td>
                            <td>
                                <form method="post" action="clientes.php" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">
                                    <input type="hidden" name="eliminar_id" value="<?php echo $row['id']; ?>">
                                    <input type="submit" name="eliminar_usuario" value="Eliminar" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </section>
    </main>

    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <div class="container mx-auto">
            <p>Derechos reservados &copy; <?php echo date("Y"); ?> Honoris.</p>
        </div>
    </footer>

    <?php
    // Incluir el script tabla_dinamica.js si se aplica a esta tabla
    // <script src="../public/js/tabla_dinamica.js"></script>
    ?>
</body>
</html>