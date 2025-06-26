<?php
// Incluir la lógica compartida para ubicaciones y la conexión a la BD.
// La ruta es relativa desde 'admin' a 'includes'.
require_once __DIR__ . '/../includes/locations_logic.php';

// Incluir el nuevo menú unificado.
// La ruta es relativa desde 'admin' a 'includes'.
$base_url = '/honoris/'; // Asegúrate que esta URL base sea correcta para tu proyecto
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Edificios y Ciudades (Admin)</title>
    <!-- Incluir Tailwind CSS desde CDN para el menú y estructura básica -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Puedes añadir estilos específicos para esta página admin aquí
           o usar clases de Tailwind para dar estilo a las tablas y formularios. */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; } /* Estilo de fila alternado básico */
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; } /* Estilo básico para formularios */
        input[type="text"] { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; width: auto; } /* Ancho auto para inputs */
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .messages p { margin-bottom: 10px; padding: 10px; border-radius: 4 dapp; }
        .messages p[style*='color:red'] { background-color: #fdd; border: 1px solid #fbc; } /* Estilo para mensajes de error */
        .messages p[style*='color:orange'] { background-color: #ffe; border: 1px solid #ffc; } /* Estilo para advertencias */
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Edificios y Ciudades (Admin)</h2>

            <div class="messages">
                <?php
                // Mostrar mensajes de las operaciones (desde locations_logic.php)
                foreach ($location_messages as $msg) {
                    echo $msg;
                }
                ?>
            </div>

            <!-- Formulario para Agregar Ciudad -->
            <h3>Agregar Ciudad</h3>
            <form method="post">
                <div>
                    <label for="nombre_ciudad">Nombre de la Ciudad:</label>
                    <input type="text" name="nombre_ciudad" id="nombre_ciudad" required>
                </div>
                <div class="mt-4">
                    <input type="submit" name="agregar_ciudad" value="Agregar Ciudad">
                </div>
            </form>

            <!-- Formulario para Agregar Edificio -->
            <h3>Agregar Edificio</h3>
            <form method="post">
                <div>
                    <label for="nombre_edificio">Nombre del Edificio:</label>
                    <input type="text" name="nombre_edificio" id="nombre_edificio" required>
                </div>
                <div class="mt-4">
                    <input type="submit" name="agregar_edificio" value="Agregar Edificio">
                </div>
            </form>

            <!-- Mostrar Formulario de Edición de Ciudad -->
            <?php
             $ciudad_a_editar = null;
             if (isset($_GET['ciudad_id'])) {
                  $ciudad_a_editar = getCiudadById($conn, $_GET['ciudad_id']);
             }
             if ($ciudad_a_editar):
            ?>
             <h3 class="mt-8">Editar Ciudad</h3>
             <form method="post" action="edificios.php">
                 <input type="hidden" name="ciudad_id" value="<?php echo $ciudad_a_editar['id']; ?>">
                 <div>
                      <label for="edit_nombre_ciudad">Nombre:</label>
                      <input type="text" name="nombre_ciudad" id="edit_nombre_ciudad" value="<?php echo htmlspecialchars($ciudad_a_editar['nombre']); ?>" required>
                 </div>
                 <div class="mt-4">
                      <input type="submit" name="actualizar_ciudad" value="Actualizar Ciudad">
                 </div>
             </form>
            <?php endif; ?>

             <!-- Mostrar Formulario de Edición de Edificio -->
            <?php
             $edificio_a_editar = null;
             if (isset($_GET['edificio_id'])) {
                  $edificio_a_editar = getEdificioById($conn, $_GET['edificio_id']);
             }
             if ($edificio_a_editar):
            ?>
             <h3 class="mt-8">Editar Edificio</h3>
             <form method="post" action="edificios.php">
                 <input type="hidden" name="edificio_id" value="<?php echo $edificio_a_editar['id']; ?>">
                 <div>
                      <label for="edit_nombre_edificio">Nombre:</label>
                      <input type="text" name="nombre_edificio" id="edit_nombre_edificio" value="<?php echo htmlspecialchars($edificio_a_editar['nombre']); ?>" required>
                 </div>
                 <div class="mt-4">
                      <input type="submit" name="actualizar_edificio" value="Actualizar Edificio">
                 </div>
             </form>
            <?php endif; ?>


            <!-- Lista de Ciudades -->
            <h3 class="mt-8">Lista de Ciudades</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $ciudades = getAllCiudades($conn); // Obtener ciudades usando la función de lógica
                    foreach ($ciudades as $row):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td>
                                <a href="edificios.php?ciudad_id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline mr-2">Editar</a>
                                <form method="post" action="edificios.php" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta ciudad?');">
                                    <input type="hidden" name="eliminar_ciudad_id" value="<?php echo $row['id']; ?>">
                                    <input type="submit" name="eliminar_ciudad" value="Eliminar" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Lista de Edificios -->
            <h3 class="mt-8">Lista de Edificios</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                     $edificios = getAllEdificios($conn); // Obtener edificios usando la función de lógica
                     foreach ($edificios as $row):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td>
                                <a href="edificios.php?edificio_id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline mr-2">Editar</a>
                                <form method="post" action="edificios.php" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este edificio?');">
                                    <input type="hidden" name="eliminar_edificio_id" value="<?php echo $row['id']; ?>">
                                    <input type="submit" name="eliminar_edificio" value="Eliminar" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
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
     // Incluir el script tabla_dinamica.js si se aplica a estas tablas
     // Si quieres aplicar tabla_dinamica.js a las tablas de ciudades y edificios,
     // asegúrate de que el JS pueda manejar múltiples tablas o inicialízalo para cada una.
     // Por ahora, no lo incluimos automáticamente ya que el original edificios.php no lo hacía.
     // <script src="../public/js/tabla_dinamica.js"></script>
     ?>

</body>
</html>