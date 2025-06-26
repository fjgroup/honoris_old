<?php
// Incluir la lógica compartida para solicitudes y la conexión a la BD.
// Esta lógica incluye el procesamiento del formulario de agregar solicitud ($messages).
// La ruta es relativa desde 'public' a 'includes'.
require_once __DIR__ . '/../includes/solicitudes_logic.php';

// Necesitamos lógica de usuarios para obtener la lista de clientes para el select del formulario
require_once __DIR__ . '/../includes/users_logic.php';
// Necesitamos lógica de ubicaciones para obtener la lista de edificios y ciudades
require_once __DIR__ . '/../includes/locations_logic.php';


// Obtener los datos para los selects del formulario ANTES de renderizar el HTML
$clientes = getUsersByType($conn, 'cliente'); // Usar lógica de usuarios
$edificios = getAllEdificios($conn); // Usar lógica de ubicaciones
$ciudades = getAllCiudades($conn); // Usar lógica de ubicaciones

// Obtener los datos para la tabla de solicitudes no relacionadas
$solicitudes_no_relacionadas = getUnrelatedSolicitudes($conn);


// Incluir el nuevo menú unificado.
$base_url = '/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Solicitudes</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
     <style>
        /* Mantén aquí tus estilos personalizados si los tienes,
           o usa clases de Tailwind para dar estilo al formulario y la tabla */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; } /* Estilo de fila alternado básico */
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; } /* Estilo básico para formularios */
        input[type="text"], select { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .messages p { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
        .messages p[style*='color:red'] { background-color: #fdd; border: 1px solid #fbc; }
        .messages p[style*='color:green'] { background-color: #dfd; border: 1px solid #bfb; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Solicitudes</h2>

             <div class="messages">
                 <?php
                 // Mostrar mensajes de las operaciones (desde solicitudes_logic.php)
                 foreach ($messages as $msg) {
                     echo $msg;
                 }
                 ?>
            </div>

            <h3>Agregar Solicitud</h3>
            <form method="post">
                <div class="mb-4">
                     <label for="cliente_id" class="block text-gray-700 text-sm font-bold mb-2">Cliente (Honoris):</label>
                     <select name="cliente_id" id="cliente_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                         <option value="">-- Seleccione Cliente --</option>
                         <?php foreach ($clientes as $row): ?>
                             <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                         <?php endforeach; ?>
                     </select>
                </div>
                 <div class="mb-4">
                     <label for="edificio_id" class="block text-gray-700 text-sm font-bold mb-2">Edificio:</label>
                     <select name="edificio_id" id="edificio_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                         <option value="">-- Seleccione Edificio --</option>
                         <?php foreach ($edificios as $row): ?>
                             <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                         <?php endforeach; ?>
                     </select>
                </div>
                 <div class="mb-4">
                     <label for="ciudad_id" class="block text-gray-700 text-sm font-bold mb-2">Ciudad:</label>
                     <select name="ciudad_id" id="ciudad_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                         <option value="">-- Seleccione Ciudad --</option>
                         <?php foreach ($ciudades as $row): ?>
                             <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                         <?php endforeach; ?>
                     </select>
                </div>
                 <div class="flex items-center justify-between">
                     <input type="submit" name="agregar_solicitud" value="Agregar Solicitud" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                 </div>
            </form>


            <h3 class="mt-8 text-xl font-semibold mb-4">Lista de Solicitudes No Relacionadas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ciudad</th>
                        <th>Cliente</th>
                        <th>Edificio</th>
                        </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_no_relacionadas as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['ciudad']); ?></td>
                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($row['edificio']); ?></td>
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
    // La ruta es relativa desde 'public' a 'public/js'
    ?>
    <script src="./js/tabla_dinamica.js"></script>

</body>
</html>