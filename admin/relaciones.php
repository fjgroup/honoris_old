<?php
// Incluir la lógica compartida para relaciones y la conexión a la BD.
require_once __DIR__ . '/../includes/relations_logic.php';
// Necesitamos lógica de solicitudes para obtener solicitudes no relacionadas
require_once __DIR__ . '/../includes/solicitudes_logic.php';
// Necesitamos lógica de propiedades (aunque la obtención por ID de solicitud la hacemos via AJAX endpoint)
// require_once __DIR__ . '/../includes/properties_logic.php'; // No es estrictamente necesario aquí si solo usamos el endpoint
// Necesitamos lógica de usuarios para obtener nombres de propietario/cliente (aunque las funciones de relaciones ya los traen)
// require_once __DIR__ . '/../includes/users_logic.php'; // No es estrictamente necesario aquí

// Si se procesó una acción POST (agregar/eliminar) en relations_logic.php
// y generó mensajes de éxito, redirigir para evitar reenvío de formulario.
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($relation_messages)) {
     $success_messages_exist = false;
     foreach($relation_messages as $msg) {
         if (strpos($msg, "<p style='color: red;'>") === false && strpos($msg, "<p style='color: orange;'>") === false) {
             $success_messages_exist = true;
             break;
         }
     }
     if ($success_messages_exist || isset($_POST['eliminar_relacion'])) {
         // Redirigir, potencialmente manteniendo el estado del filtro si se aplicó antes del POST
         $redirect_url = $_SERVER['PHP_SELF'];
         if (isset($_POST['filtrar_propietarios'])) {
              $redirect_url .= "?filter=no_f"; // Añadir parámetro para re-aplicar el filtro
         }
         header("Location: " . $redirect_url);
         exit();
     }
}

// Determinar si se debe aplicar el filtro
$apply_filter = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['filtrar_propietarios'])) {
    $apply_filter = true;
} elseif (isset($_GET['filter']) && $_GET['filter'] == 'no_f') {
     $apply_filter = true; // Re-aplicar filtro si viene en GET después de redirección
}


// Obtener datos para los selects y la tabla ANTES de renderizar el HTML
$unrelated_solicitudes = getUnrelatedSolicitudes($conn); // Usar lógica de solicitudes

// Obtener la lista de todas las relaciones, aplicando el filtro si es necesario
$all_relations = getAllRelations($conn, $apply_filter);


// Incluir el nuevo menú unificado.
$base_url = '/'; // Asegúrate que esta URL base sea correcta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Relaciones (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
     <style>
        /* Puedes añadir estilos específicos o usar clases de Tailwind */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; border-radius: 5px; }
         input[type="submit"], button[type="button"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;}
         input[type="submit"]:hover, button[type="button"]:hover { background-color: #45a049; }
         select { padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
         .messages p { margin-bottom: 10px; padding: 10px; border-radius: 4px; }
         .messages p[style*='color: red;'] { background-color: #fdd; border: 1px solid #fbc; } /* Estilo para mensajes de error */
         .messages p[style*='color: green;'] { background-color: #dfd; border: 1px solid #bfb; } /* Estilo para mensajes de éxito */
         .messages p[style*='color: orange;'] { background-color: #ffe; border: 1px solid #ffc; } /* Estilo para advertencias */

    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php include __DIR__ . '/../includes/menu.php'; // Incluir el nuevo menú unificado ?>

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <section>
            <h2 class="text-2xl font-semibold mb-4">Gestión de Relaciones (Admin)</h2>

            <div class="messages">
                <?php
                // Mostrar mensajes de las operaciones (desde relations_logic.php)
                foreach ($relation_messages as $msg) {
                    echo $msg;
                }
                ?>
            </div>


            <h3>Conectar Solicitud con Propiedad</h3>
            <form method="post">
                <div>
                     <label for="solicitud_id">Solicitud:</label>
                     <select name="solicitud_id" id="solicitud_id" required>
                         <option value="">Seleccione una solicitud</option>
                         <?php
                         // Obtener solicitudes no relacionadas usando la función de lógica
                         foreach ($unrelated_solicitudes as $row):
                             // Mantener la selección si se recarga la página (ej: por un error de validación)
                             $selected = (isset($_POST['solicitud_id']) && $_POST['solicitud_id'] == $row['id']) ? 'selected' : '';
                             echo "<option value='" . htmlspecialchars($row['id']) . "' $selected>" .
                                  "ID: " . htmlspecialchars($row['id']) .
                                  " - Cliente: " . htmlspecialchars($row['cliente']) .
                                  " - Edif: " . htmlspecialchars($row['edificio']) .
                                  " - " . htmlspecialchars($row['ciudad']) .
                                  "</option>";
                         endforeach;
                         ?>
                     </select>
                     <button type="button" id="buscar_propiedades" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">Buscar Propiedades</button>
                 </div>

                 <div class="mt-4">
                     <label for="propiedad_id">Propiedad:</label>
                     <select name="propiedad_id" id="propiedad_id" required disabled>
                         <option value="">Seleccione una propiedad</option>
                          <?php
                          // Si hubo un POST que falló y se mantuvo la solicitud_id,
                          // podrías querer rellenar este select con las propiedades correspondientes.
                          // Esto haría la UX un poco mejor en caso de errores.
                          // Necesitarías incluir properties_logic.php para usar getPropertiesBySolicitudId aquí.
                          /*
                          if (isset($_POST['solicitud_id']) && filter_var($_POST['solicitud_id'], FILTER_VALIDATE_INT) !== false) {
                               require_once __DIR__ . '/../includes/properties_logic.php';
                               $solicitud_id_fallida = filter_var($_POST['solicitud_id'], FILTER_VALIDATE_INT);
                               $propiedades_fallidas = getPropertiesBySolicitudId($conn, $solicitud_id_fallida);
                               if (!empty($propiedades_fallidas)) {
                                   foreach ($propiedades_fallidas as $row) {
                                        $selected = (isset($_POST['propiedad_id']) && $_POST['propiedad_id'] == $row['id']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row['id']) . '" ' . $selected . '>' .
                                             'ID: ' . htmlspecialchars($row['id']) .
                                             ' - Propietario: ' . htmlspecialchars($row['propietario']) .
                                             ' - Edificio: ' . htmlspecialchars($row['edificio']) .
                                             ' - Ciudad: ' . htmlspecialchars($row['ciudad']) .
                                             '</option>';
                                   }
                                    echo '<script>document.getElementById("propiedad_id").disabled = false;</script>'; // Habilitar si se rellenó
                               }
                          }
                          */
                          ?>
                     </select>
                 </div>

                 <div class="mt-6">
                     <input type="submit" name="agregar_relacion" value="Agregar Relación" id="submitBtn" disabled>
                 </div>
            </form>

             <div class="mt-8">
                <form method="post" style="display: inline;">
                    <input type="submit" name="mostrar_todos" value="Mostrar Todas las Relaciones" class="bg-gray-500 hover:bg-gray-700 py-2 px-4 rounded">
                </form>
                <form method="post" style="display: inline;">
                    <input type="submit" name="filtrar_propietarios" value="Ocultar Propietarios *F" class="bg-orange-500 hover:bg-orange-700 py-2 px-4 rounded">
                </form>
             </div>


            <h3 class="mt-8">Lista de Relaciones</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID R.</th>
                        <th>ID Sol</th>
                        <th>Cliente (Solicitud)</th>
                        <th>Edificio (Solicitado)</th>
                        <th>Ciudad (Solicitada)</th>
                        <th>ID Prop</th>
                        <th>Propietario (Propiedad)</th>
                        <th>Edificio (Prop)</th>
                        <th>Ciudad (Prop)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_relations as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['relacion_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['solicitud_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($row['edificio_solicitado']); ?></td>
                            <td><?php echo htmlspecialchars($row['ciudad_solicitada']); ?></td>
                            <td><?php echo htmlspecialchars($row['propiedad_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['propietario']); ?></td>
                            <td><?php echo htmlspecialchars($row['edificio_propiedad']); ?></td>
                            <td><?php echo htmlspecialchars($row['ciudad_propiedad']); ?></td>
                            <td>
                                <form method="post" action="relaciones.php" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta relación?');">
                                    <input type="hidden" name="eliminar_relacion_id" value="<?php echo $row['relacion_id']; ?>">
                                    <input type="submit" name="eliminar_relacion" value="Eliminar" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-xs">
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

    <script>
        $(document).ready(function() {
            $('#buscar_propiedades').click(function() {
                var solicitud_id = $('#solicitud_id').val();
                if (solicitud_id) {
                    $.ajax({
                        // *** RUTA DEL NUEVO ENDPOINT AJAX ***
                        // La ruta es relativa desde la carpeta 'admin' a 'public/api'
                        url: '../public_web/api/get_properties_for_solicitud.php',
                        type: 'GET',
                        data: { solicitud_id: solicitud_id },
                        success: function(data) {
                            $('#propiedad_id').html(data).prop('disabled', false);
                            // Habilitar el botón de agregar relación solo si hay opciones de propiedad además de la opción por defecto
                            if ($('#propiedad_id option').length > 1 && $('#propiedad_id').val() !== '') {
                                $('#submitBtn').prop('disabled', false);
                            } else {
                                $('#submitBtn').prop('disabled', true);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Error en la solicitud AJAX:", textStatus, errorThrown);
                            $('#propiedad_id').html('<option value="">Error al cargar propiedades</option>').prop('disabled', true);
                            $('#submitBtn').prop('disabled', true);
                        }
                    });
                } else {
                    // Limpiar y deshabilitar si no se selecciona ninguna solicitud
                    $('#propiedad_id').html('<option value="">Seleccione una propiedad</option>').prop('disabled', true);
                    $('#submitBtn').prop('disabled', true);
                }
            });

            // Lógica inicial y al cambiar el select de propiedad para habilitar/deshabilitar el botón de agregar
             $('#propiedad_id').change(function() {
                  if ($(this).val()) {
                       $('#submitBtn').prop('disabled', false);
                  } else {
                       $('#submitBtn').prop('disabled', true);
                  }
             });

            // Deshabilitar el botón de agregar al cargar la página si no hay una propiedad seleccionada inicialmente
            if (!$('#propiedad_id').val()) {
                 $('#submitBtn').prop('disabled', true);
            }

        });
    </script>

    <?php
    // Incluir el script tabla_dinamica.js si se aplica a esta tabla
    // La ruta es relativa desde 'admin' a 'public/js'
    ?>
    <script src="../public_web/js/tabla_dinamica.js"></script>

</body>
</html>