<?php
// Incluir la lógica compartida para usuarios y la conexión a la BD.
// La ruta es relativa desde 'admin' a 'includes'.
require_once __DIR__ . '/../includes/users_logic.php';
// También necesitaremos lógica de propiedades y solicitudes para las listas anidadas,
// pero por ahora, solo incluimos users_logic.php.
// require_once __DIR__ . '/../includes/properties_logic.php'; // La crearemos más tarde
// require_once __DIR__ . '/../includes/solicitudes_logic.php'; // Ya la creamos

// Aquí NO manejamos agregar/eliminar/actualizar directamente,
// esas acciones se realizan en clientes.php usando la lógica compartida.
// Sin embargo, la lógica de eliminación en users_logic.php ya maneja la eliminación de propiedades
// cuando se elimina un usuario, que era lo que hacía propietarios.php.

// Obtener la lista de usuarios de tipo 'propietario'
$propietarios = getUsersByType($conn, 'propietario');

// Obtener datos del propietario a editar si el parámetro 'id' está en la URL
// Aunque la edición se hace en clientes.php, podrías querer un enlace "Editar" aquí
// que redirija a la página de edición de clientes con el ID.
// O podrías replicar el formulario de edición aquí si prefieres.
// Por ahora, asumiremos que la edición principal es vía clientes.php.

// Incluir el nuevo menú unificado.
$base_url = '/honoris/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Propietarios (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Puedes añadir estilos específicos o usar clases de Tailwind */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
         /* Estilos para la lista anidada de propiedades */
         td ul { margin-top: 5px; padding-left: 20px; list-style: disc; }
         td ul li { margin-bottom: 3px; }
         /* Estilos para el formulario de edición (si decides incluirlo aquí) */
         form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
         input[type="text"] { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
         input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
         input[type="submit"]:hover { background-color: #45a049; }

    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Propietarios (Admin)</h2>

             <div class="messages">
                 <?php
                 // Mostrar mensajes si hay alguno del procesamiento POST (ej: eliminación de propietario)
                 // Aunque la eliminación se hace en clients.php, si un propietario.php
                 // tuviera su propio formulario de eliminación que postea a sí mismo,
                 // la lógica en users_logic.php lo manejaría y los mensajes aparecerían aquí.
                 foreach ($user_messages as $msg) {
                     echo $msg;
                 }
                 ?>
             </div>

             <?php
             // Opcional: Mostrar el formulario de edición si se accede con un ID
             // Puedes copiar el formulario de edición de clientes.php aquí si lo deseas,
             // pero asegúrate de que el action del formulario apunte a sí mismo (propietarios.php)
             // y que la lógica de actualización en users_logic.php se dispare correctamente
             // cuando se envíe el POST desde este formulario.
             // Por ahora, dejaremos que la edición se haga principalmente desde clientes.php.

             /*
             $propietario_a_editar = null;
             if (isset($_GET['id'])) {
                  $propietario_a_editar = getUserById($conn, $_GET['id']);
             }
             if ($propietario_a_editar):
             ?>
              <h3 class="mt-8">Editar Propietario</h3>
              <form method="post" action="propietarios.php">
                  ... formulario de edición ...
              </form>
             <?php endif; ?>
             */
             ?>


            <h3>Lista de Propietarios</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propietarios as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre']); ?></td>
                            <td>
                                <a href="clientes.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline mr-2">Editar Usuario</a>
                                <form method="post" action="propietarios.php" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este propietario y sus propiedades asociadas?');">
                                    <input type="hidden" name="eliminar_id" value="<?php echo $row['id']; ?>">
                                    <input type="submit" name="eliminar_usuario" value="Eliminar Propietario" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
                                </form>
                                 <?php /* if (!$propietario_a_editar): */ ?>
                                    <?php /* <a href="propietarios.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:underline ml-2">Editar Propietario Específico</a> */ ?>
                                 <?php /* endif; */ ?>
                            </td>
                        </tr>
                        <?php
                         // Mostrar Propiedades asociadas a este propietario
                         $propiedades_asociadas = getPropertiesByOwnerId($conn, $row['id']);
                         if (!empty($propiedades_asociadas)):
                        ?>
                             <tr>
                                 <td></td> <td colspan="2">
                                     <strong>Propiedades:</strong>
                                     <ul>
                                         <?php foreach ($propiedades_asociadas as $prop): ?>
                                             <li>
                                                 <?php echo htmlspecialchars($prop['edificio']) . " en " . htmlspecialchars($prop['ciudad']); ?>
                                                 <?php
                                                  // Mostrar clientes que solicitan este edificio/ciudad
                                                  $solicitudes_para_prop = getSolicitudesByEdificioCiudad($conn, $prop['edificio'], $prop['ciudad']);
                                                  if (!empty($solicitudes_para_prop)):
                                                     $clientes_interesados = array_column($solicitudes_para_prop, 'cliente');
                                                     echo " (Solicitado por: " . htmlspecialchars(implode(", ", $clientes_interesados)) . ")";
                                                  endif;
                                                 ?>
                                             </li>
                                         <?php endforeach; ?>
                                     </ul>
                                 </td>
                                  <td></td><td></td> </tr>
                        <?php else: ?>
                             <tr>
                                 <td></td> <td colspan="2"><p>No posee propiedades asignadas.</p></td>
                                  <td></td><td></td> </tr>
                        <?php endif; ?>

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