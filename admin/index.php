<?php
// Este es el punto de entrada al área de administración.
// No necesita mucha lógica de base de datos aquí a menos que quieras mostrar estadísticas, etc.

// Asegúrate de que la base URL esté definida si la necesitas aquí por alguna razón,
// aunque el menú ya la incluye.
$base_url = '/'; // Asegúrate que esta URL base sea correcta para tu proyecto
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Puedes añadir estilos específicos para el dashboard admin aquí */
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <h2 class="text-2xl font-semibold mb-4 text-center">Panel de Administración Honoris</h2>
        <p class="text-gray-700 text-center">Bienvenido al panel de administración. Utiliza el menú superior para navegar entre las diferentes secciones de gestión.</p>
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-blue-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-blue-800 mb-3">Gestión de Usuarios</h3>
                <p class="text-gray-700 mb-4">Administra clientes y propietarios del sistema.</p>
                <a href="<?php echo $base_url; ?>admin/clientes.php" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Ir a Usuarios</a>
            </div>

             <div class="bg-green-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-green-800 mb-3">Gestión de Ubicaciones</h3>
                <p class="text-gray-700 mb-4">Administra las ciudades y edificios disponibles.</p>
                <a href="<?php echo $base_url; ?>admin/edificios.php" class="inline-block bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Ir a Ubicaciones</a>
            </div>

             <div class="bg-purple-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-purple-800 mb-3">Gestión de Propiedades</h3>
                <p class="text-gray-700 mb-4">Administra las propiedades asociadas a propietarios.</p>
                <a href="<?php echo $base_url; ?>admin/propiedades.php" class="inline-block bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">Ir a Propiedades</a>
            </div>

             <div class="bg-yellow-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-yellow-800 mb-3">Gestión de Solicitudes</h3>
                <p class="text-gray-700 mb-4">Administra las solicitudes de clientes.</p>
                <a href="<?php echo $base_url; ?>admin/solicitudes.php" class="inline-block bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">Ir a Solicitudes</a>
            </div>

             <div class="bg-red-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-red-800 mb-3">Gestión de Relaciones</h3>
                <p class="text-gray-700 mb-4">Vincula solicitudes con propiedades.</p>
                <a href="<?php echo $base_url; ?>admin/relaciones.php" class="inline-block bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Ir a Relaciones</a>
            </div>

             <?php /*
             // Ejemplo de cómo agregar un enlace a Propietarios si quieres una página dedicada solo a ellos
             <div class="bg-orange-100 p-6 rounded-lg shadow-md text-center">
                <h3 class="text-xl font-semibold text-orange-800 mb-3">Ver Propietarios</h3>
                <p class="text-gray-700 mb-4">Visualiza detalles de propietarios y sus propiedades.</p>
                <a href="<?php echo $base_url; ?>admin/propietarios.php" class="inline-block bg-orange-500 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">Ir a Propietarios</a>
            </div>
            */ ?>

        </div>


    </main>

    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <div class="container mx-auto">
            <p>Derechos reservados &copy; <?php echo date("Y"); ?> Honoris.</p>
        </div>
    </footer>

</body>
</html>