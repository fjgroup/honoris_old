<?php
// Incluir el archivo de configuracion global
require_once __DIR__ . '/config.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Ejemplo de inclusion de fuente MedievalSharp de Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=MedievalSharp&display=swap" rel="stylesheet">
    <style>
        /* Aplicar la fuente medieval al titulo principal */
        .font-medieval {
            font-family: 'MedievalSharp', cursive;
        }

        /* Estilos adicionales para las tarjetas de enlaces */
        .link-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/includes/menu.php'; ?>

    <main class="container mx-auto mt-12 p-6 flex-grow">
        <h2 class="text-5xl md:text-6xl font-medieval mb-12 text-center text-gray-700">Bienvenido a Honoris</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Tarjeta 1: Solicitudes -->
            <a href="<?php echo $base_url; ?>public_web/solicitudes_honoris.php" class="link-card block bg-gradient-to-r from-blue-500 to-blue-600 text-white p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold mb-2">Ver Solicitudes</h3>
                <p class="text-sm opacity-90">Gestiona y revisa las solicitudes pendientes.</p>
            </a>

            <!-- Tarjeta 2: Edificios -->
            <a href="<?php echo $base_url; ?>public_web/propiedades_honoris.php" class="link-card block bg-gradient-to-r from-green-500 to-green-600 text-white p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold mb-2">Ver Edificios</h3>
                <p class="text-sm opacity-90">Explora los edificios asociados y sus detalles.</p>
            </a>

            <!-- Tarjeta 3: Calculadora de Refinado -->
            <a href="<?php echo $base_url; ?>calculator/leather_calculator.php" class="link-card block bg-gradient-to-r from-yellow-500 to-yellow-600 text-white p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold mb-2">Calculadora de Refinado</h3>
                <p class="text-sm opacity-90">Optimiza tus costos de refinamiento de materiales.</p>
            </a>

            <!-- Tarjeta 4: Calculadora de Crafting -->
            <a href="<?php echo $base_url; ?>calculator/crafting_calculator.php" class="link-card block bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-lg shadow-lg text-center">
                <h3 class="text-xl font-semibold mb-2">Calculadora de Crafting</h3>
                <p class="text-sm opacity-90">Calcula la rentabilidad de tus fabricaciones.</p>
            </a>
         </div>
    </main>

    <footer class="bg-gray-800 text-white text-center p-4 mt-8">
        <div class="container mx-auto">
            <p>Derechos reservados &copy; <?php echo date("Y"); ?> Honoris.</p>
        </div>
    </footer>

</body>
</html>