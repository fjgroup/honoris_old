<?php
// Incluir la lógica compartida para propiedades (solo para obtener datos) y la conexión a la BD.
// La ruta es relativa desde 'public' a 'includes'.
require_once __DIR__ . '/../includes/properties_logic.php';

// Obtener datos para la tabla ANTES de renderizar el HTML
$all_properties = getAllProperties($conn); // Obtener todas las propiedades usando lógica de propiedades

// Incluir el nuevo menú unificado.
$base_url = '/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Propiedades</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
     <style>
        /* Mantén aquí los estilos originales de Honoris si los hay,
           o empieza a aplicar Tailwind a la tabla */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Lista de Propiedades</h2>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Propietario</th>
                        <th>Edificio</th>
                        <th>Ciudad</th>
                        </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_properties as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['propietario']); ?></td>
                            <td><?php echo htmlspecialchars($row['edificio']); ?></td>
                            <td><?php echo htmlspecialchars($row['ciudad']); ?></td>
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
    ?>
    <script src="./js/tabla_dinamica.js"></script>

</body>
</html>