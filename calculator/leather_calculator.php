<?php
// Incluir el archivo de configuracion global
require_once __DIR__ . '/../config.php'; // Sube un nivel para encontrar config.php en la raíz
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Calculadora de Cuero</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Mantener tus estilos personalizados aqu\u00ed si los tienes */
        /* Opcional: Estilos b\u00e1sicos si no tienes un CSS aparte */
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }
        /* CAMBIAMOS .container A .calculator-container PARA EVITAR CONFLICTO CON TAILWIND */
        .calculator-container { /* <-- CLASE RENOMBRADA */
            max-width: 800px; /* Ancho m\u00e1ximo del contenido */
            margin: 20px auto; /* Centra el contenedor */
            padding: 20px;
            background-color: #fff; /* El fondo blanco que quer\u00edas solo para el contenido */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"], select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* Estilos para botones */
        button {
             padding: 10px 15px;
             border: none;
             border-radius: 4px;
             cursor: pointer;
             margin-right: 10px; /* Espacio entre botones si hay varios */
             font-weight: bold;
        }
        /* Estilo espec\u00edfico para el bot\u00f3n Calcular */
         button[type="submit"] {
             background-color: #3b82f6; /* blue-500 */
             color: white;
         }
         button[type="submit"]:hover {
             background-color: #2563eb; /* blue-600 */
         }
         /* Estilo espec\u00edfico para el bot\u00f3n Guardar */
         button#saveCalculationButton { /* Cambiado de loadCalculationButton a saveCalculationButton para mayor claridad en HTML */
             background-color: #10b981; /* emerald-500 */
             color: white;
         }
          button#saveCalculationButton:hover {
             background-color: #059669; /* emerald-600 */
         }
         /* Estilo para botones de Cargar/Eliminar en el historial */
          .history-item button {
             padding: 4px 8px; /* Botones m\u00e1s peque\u00f1os */
             font-size: 0.8rem;
             margin-left: 5px;
             margin-right: 0; /* Eliminar margen a la derecha */
         }
          .history-item button.load-btn {
             background-color: #60a5fa; /* blue-400 */
             color: white;
         }
          .history-item button.load-btn:hover {
              background-color: #3b82f6; /* blue-500 */
          }
          .history-item button.delete-btn {
             background-color: #f87171; /* red-400 */
             color: white;
         }
          .history-item button.delete-btn:hover {
              background-color: #ef4444; /* red-500 */
          }


        #result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        /* Asegura que el body se expanda para empujar el footer hacia abajo */
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; }

        /* Estilos para el \u00e1rea del historial */
         #calculationHistory {
             margin-bottom: 20px;
             padding: 15px;
             border: 1px solid #ccc;
             border-radius: 8px;
             background-color: #f9f9f9;
         }
         #calculationHistory h3 {
             margin-top: 0;
             margin-bottom: 10px;
             font-size: 1.2rem;
             font-weight: bold;
         }
          #calculationHistory ul {
              list-style: none; /* Quitar vi\u00f1etas */
              padding: 0;
              margin: 0;
          }
           #calculationHistory li {
               margin-bottom: 8px;
               padding: 8px;
               border-bottom: 1px solid #eee;
               display: flex; /* Usar flexbox para alinear nombre y botones */
               align-items: center;
               justify-content: space-between; /* Espacio entre nombre y botones */
                flex-wrap: wrap; /* Permitir que los elementos se envuelvan en pantallas peque\u00f1as */
           }
           #calculationHistory li span { /* Estilo para el nombre del c\u00e1lculo */
               cursor: pointer; /* Indica que se puede hacer clic para cargar */
               color: #059669; /* Color esmeralda para el nombre */
               text-decoration: underline; /* Subrayar para indicar que es un enlace clickeable */
               flex-grow: 1; /* Permitir que el nombre ocupe espacio disponible */
               margin-right: 10px; /* Espacio entre nombre y botones */
           }
            #calculationHistory li span:hover {
                color: #047857; /* Color m\u00e1s oscuro al pasar el rat\u00f3n */
            }
           #calculationHistory li .history-actions { /* Contenedor para los botones */
               flex-shrink: 0; /* Evitar que los botones se encojan */
           }


    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php
    // Incluir el nuevo men\u00fa unificado.
    // La ruta es relativa desde 'calculator' a 'includes'.
    include __DIR__ . '/../includes/menu.php';
    ?>

    <main class="calculator-container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <h1 class="text-2xl font-bold mb-6 text-center">Calculadora de Producción y Rentabilidad para el Refinado</h1>

        <div id="calculationHistory">
            <h3>Historial de Cálculos Guardados</h3>
            <ul id="historyList">
                <li>No hay cálculos guardados.</li>
            </ul>
        </div>
        <form id="calculatorForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php // Secciones de inputs principales ?>
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-4 border p-4 rounded-md bg-gray-50">
                <div>
                    <label for="rental_cost" class="block text-sm font-medium text-gray-700">Costo Alquiler (%)</label>
                    <input type="number" step="0.1" id="rental_cost" name="rental_cost" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="0" required min="0">
                </div>
                <div>
                    <label for="purchase_percentage" class="block text-sm font-medium text-gray-700">Tasa Compra (%)</label>
                    <select id="purchase_percentage" name="purchase_percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="0">0%</option>
                         <option value="2.5" selected>2.5%</option>
                    </select>
                </div>
                <div>
                    <label for="sales_percentage" class="block text-sm font-medium text-gray-700">Tasa Venta (%)</label>
                    <select id="sales_percentage" name="sales_percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="0">0%</option>
                         <option value="4" selected>4%</option>
                         <option value="8">8%</option>
                    </select>
                </div>
                <div>
                    <label for="tax_percentage" class="block text-sm font-medium text-gray-700">Impuestos (%)</label>
                     <select id="tax_percentage" name="tax_percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="0">0%</option>
                         <option value="2.5" selected>2.5%</option>
                         <option value="5.0">5.0%</option>
                         <option value="7.5">7.5%</option>
                         <option value="10.0">10.0%</option>
                         <option value="12.5">12.5%</option>
                         <option value="15.0">15.0%</option>
                         <option value="17.5">17.5%</option>
                         <option value="20.0">20.0%</option>
                     </select>
                </div>
                <div>
                    <label for="return_percentage" class="block text-sm font-medium text-gray-700">Retorno (%)</label>
                    <select id="return_percentage" name="return_percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="36.7" selected>36.7</option>
                         <option value="43.5">43.5 (Premium)</option>
                         <option value="53.9">53.9 (Especializado)</option>
                    </select>
                </div>
            </div>

            <?php // Secci\u00f3n de selecci\u00f3n de Tiers y Cantidad Inicial ?>
             <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 border p-4 rounded-md bg-gray-50">
                 <div>
                     <label for="starting_tier" class="block text-sm font-medium text-gray-700">Tier de Inicio</label>
                     <select id="starting_tier" name="starting_tier" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="t2">T2</option>
                          <option value="t3">T3</option>
                          <option value="t4">T4</option>
                          <option value="t5">T5</option>
                          <option value="t6">T6</option>
                          <option value="t7">T7</option>
                     </select>
                 </div>
                 <div>
                     <label for="crafting_limit_tier" class="block text-sm font-medium text-gray-700">Tier L\u00edmite de Crafteo</label>
                     <select id="crafting_limit_tier" name="crafting_limit_tier" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                         <option value="t2">T2</option>
                          <option value="t3">T3</option>
                          <option value="t4">T4</option>
                          <option value="t5">T5</option>
                          <option value="t6">T6</option>
                          <option value="t7">T7</option>
                          <option value="t8" selected>T8</option>
                     </select>
                 </div>
                 <div>
                     <label id="label_initial_hides" for="initial_hides" class="block text-sm font-medium text-gray-700">Cantidad Inicial de Recursos T2</label>
                     <input type="number" step="1" id="initial_hides" name="initial_quantities[hides]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="100" required min="0">
                 </div>
             </div>

             <?php // Secci\u00f3n de Precio de Compra de Material Anterior (din\u00e1mica) ?>
              <div id="buyingPriceSection" class="md:col-span-2 border p-4 rounded-md bg-gray-50" style="display: none;">
                  <h3 class="text-lg font-semibold mb-2">Precio de Compra de Material Anterior</h3>
                  <div id="buyingPriceInput" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                       <div>
                            <label id="label_buying_price" for="buying_price_prev_tier" class="block text-sm font-medium text-gray-700">Precio Compra Refinado Anterior:</label>
                            <input type="number" step="1" id="buying_price_prev_tier" name="buying_prices[tX]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="0" required min="0">
                       </div>
                  </div>
              </div>

            <div class="md:col-span-2 border p-4 rounded-md bg-gray-50">
                <h3 class="text-lg font-semibold mb-2">Costos de Materia Prima por Tier</h3>
                <div id="rawHideCostsInputs" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    </div>
            </div>

            <div class="md:col-span-2 border p-4 rounded-md bg-gray-50">
                <h3 class="text-lg font-semibold mb-2">Precio de Venta Refinado por tier</h3>
                <div id="sellingPricesInputs" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    </div>
            </div>

            <div class="md:col-span-2 text-center flex justify-center space-x-4">
                 <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                     Calcular
                 </button>
                 <button type="button" id="loadCalculationButton" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                     Guardar Cálculo
                 </button>
             </div>


        </form>

        <?php // Secciones de resultados y errores ?>
        <div id="validationErrors" class="mt-6 text-red-600">
             </div>

        <div id="summaryResults" class="mt-8 border-t pt-6">
             <h2 class="text-xl font-bold mb-4">Resumen General</h2>
             </div>

        <div id="tierResults" class="mt-8 border-t pt-6">
             <h2 class="text-xl font-bold mb-4">Resultados por Tier</h2>
             </div>


    </main>

    <?php // Footer y Scripts ?>
     <footer class="bg-gray-800 text-white text-center p-4 mt-8">
         <div class="container mx-auto">
             <p>&copy; <?php echo date("Y"); ?> Honoris. Todos los derechos reservados.</p>
         </div>
     </footer>

    <script src="./frontend/js/script.js"></script>

    <?php
     // Si tu calculadora usaba jQuery o cualquier otra librer\u00eda, incl\u00fayela aqu\u00ed si es necesario
     ?>

</body>
</html>