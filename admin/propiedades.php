<?php
// Incluir la lógica compartida para propiedades y la conexión a la BD.
require_once __DIR__ . '/../includes/properties_logic.php';
// También necesitamos la lógica de usuarios para obtener la lista de propietarios
require_once __DIR__ . '/../includes/users_logic.php';
// Y la lógica de ubicaciones para obtener la lista de edificios y ciudades
require_once __DIR__ . '/../includes/locations_logic.php';


// Si se procesó una acción POST (agregar/eliminar) en properties_logic.php
// y generó mensajes de éxito, redirigir para evitar reenvío de formulario.
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($property_messages)) {
    $success_messages_exist = false;
    foreach($property_messages as $msg) {
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


// Obtener datos para los selects y la tabla ANTES de renderizar el HTML
$propietarios = getUsersByType($conn, 'propietario'); // Usar lógica de usuarios
$edificios = getAllEdificios($conn); // Usar lógica de ubicaciones
$ciudades = getAllCiudades($conn); // Usar lógica de ubicaciones
$all_properties = getAllProperties($conn); // Obtener todas las propiedades usando lógica de propiedades

// Incluir el nuevo menú unificado.
$base_url = '/honoris/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Propiedades (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Puedes añadir estilos específicos o usar clases de Tailwind */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        select { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
         .messages p { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
         .messages p[style*='color:red'] { background-color: #fdd; border: 1px solid #fbc; }
         .messages p[style*='color:orange'] { background-color: #ffe; border: 1px solid #ffc; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Propiedades (Admin)</h2>

            <div class="messages">
                <?php
                // Mostrar mensajes de las operaciones (desde properties_logic.php)
                foreach ($property_messages as $msg) {
                    echo $msg;
                }
                ?>
            </div>


            <h3>Asignar Propiedad a Propietario</h3>
            <form method="post">
                <div>
                    <label for="propietario_id">Propietario:</label>
                    <select name="propietario_id" id="propietario_id" required>
                         <option value="">-- Seleccione Propietario --</option>
                        <?php foreach ($propietarios as $row): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-4">
                    <label for="edificio_id">Edificio:</label>
                    <select name="edificio_id" id="edificio_id" required>
                         <option value="">-- Seleccione Edificio --</option>
                        <?php foreach ($edificios as $row): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-4">
                    <label for="ciudad_id">Ciudad:</label>
                    <select name="ciudad_id" id="ciudad_id" required>
                         <option value="">-- Seleccione Ciudad --</option>
                        <?php foreach ($ciudades as $row): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="mt-6">
                    <input type="submit" name="agregar_propiedad" value="Asignar Propiedad">
                 </div>
            </form>

            <h3 class="mt-8">Lista de Propiedades</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Propietario</th>
                        <th>Edificio</th>
                        <th>Ciudad</th>
                        <th>Acciones</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_properties as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['propietario']); ?></td>
                            <td><?php echo htmlspecialchars($row['edificio']); ?></td>
                            <td><?php echo htmlspecialchars($row['ciudad']); ?></td>
                            <td>
                                <form method="post" action="propiedades.php" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta propiedad y sus relaciones asociadas?');">
                                    <input type="hidden" name="eliminar_propiedad_id" value="<?php echo $row['id']; ?>">
                                    <input type="submit" name="eliminar_propiedad" value="Eliminar" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
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