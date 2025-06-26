<?php
// Incluir el archivo de configuracion global
require_once __DIR__ . '/../config.php'; // Sube un nivel para encontrar config.php en la raíz
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Honoris - Calculadora de Fabricacion</title>     <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Mantener tus estilos personalizados aqui si los tienes */
        /* Opcional: Estilos basicos si no tienes un CSS aparte */
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
        }
        /* Usamos .calculator-container */
        .calculator-container {
            max-width: 800px; /* Ancho maximo del contenido */
            margin: 20px auto; /* Centra el contenedor */
            padding: 20px;
            background-color: #fff; /* El fondo blanco que querias solo para el contenido */
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="number"], input[type="text"], select { /* Anadimos input text */
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
        /* Estilo especifico para el boton Calcular */
        button[type="submit"] {
            background-color: #3b82f6; /* blue-500 */
            color: white;
         }
        button[type="submit"]:hover {
            background-color: #2563eb; /* blue-600 */
         }
         /* Estilo especifico para el boton Guardar */
        button#saveProductButton { /* Cambiado de saveCalculationButton a saveProductButton */
            background-color: #10b981; /* emerald-500 */
            color: white;
         }
          button#saveProductButton:hover {
            background-color: #059669; /* emerald-600 */
          }
         /* Estilo para botones de Cargar/Eliminar en el historial */
          .history-item button {
            padding: 4px 8px; /* Botones mas pequenos */
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
        body { display: flex; flex-direction: column; min-height: 10vh; } /* Adjusted min-height for better display */
        main { flex-grow: 1; }

        /* Estilos para el area del historial */
         #productHistory { /* Cambiado de calculationHistory a productHistory */
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
         }
          #productHistory h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.2rem;
            font-weight: bold;
          }
          #productHistory ul {
              list-style: none; /* Quitar vinetas */
              padding: 0;
              margin: 0;
          }
           #productHistory li { /* Cambiado de #calculationHistory li */
              margin-bottom: 8px;
              padding: 8px;
              border-bottom: 1px solid #eee;
              display: flex; /* Usar flexbox para alinear nombre y botones */
              align-items: center;
              justify-content: space-between; /* Espacio entre nombre y botones */
              flex-wrap: wrap; /* Permitir que los elementos se envuelvan en pantallas pequenas */
           }
            #productHistory li span { /* Estilo para el nombre del producto */ /* Cambiado de nombre de calculo a producto */
              cursor: pointer; /* Indica que se puede hacer click para cargar */
              color: #059669; /* Color esmeralda para el nombre */
              text-decoration: underline; /* Subrayar para indicar que es un enlace clickeable */
              flex-grow: 1; /* Permitir que el nombre ocupe espacio disponible */
              margin-right: 10px; /* Espacio entre nombre y botones */
            }
            #productHistory li span:hover {
              color: #047857; /* Color mas oscuro al pasar el raton */
            }
           #productHistory li .history-actions { /* Contenedor para los botones */
              flex-shrink: 0; /* Evitar que los botones se encojan */
           }


    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">

    <?php
    // Incluir el nuevo menu unificado.
    // La ruta es relativa desde 'calculator' a 'includes'.
    include __DIR__ . '/../includes/menu.php';
    ?>

    <main class="calculator-container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md flex-grow">
        <h1 class="text-2xl font-bold mb-6 text-center">Calculadora de Fabricacion</h1>         <div id="productHistory">             <h3>Historial de Productos Guardados</h3>             <ul id="historyList">                 <li>No hay productos guardados.</li>             </ul>
        </div>

        <div id="validationErrors" class="mt-6 text-red-600"></div>

        <div id="summaryResults" class="mt-8 border-t pt-6">
             <h2 class="text-xl font-bold mb-4">Resumen General</h2>
             </div>

        <div id="tierResults" class="mt-8 border-t pt-6">
             <!-- Esta seccion ahora se fusiona con summaryResults, se puede dejar vacia o eliminar el div si no se usa para nada mas -->
            </div>

        <form id="craftingCalculatorForm" class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="md:col-span-2 grid grid-cols-1 md:grid-cols-5 gap-4 border p-4 rounded-md bg-gray-50">
        <div>
            <label for="rental_cost_value" class="block text-sm font-medium text-gray-700">Costo Alquiler</label>
            <input type="number" step="0.1" id="rental_cost_value" name="rental_cost_value" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="0" required min="0">
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
                     <label class="block text-sm font-medium text-gray-700">Publicación (Tasa Fija: 2.5%)</label>
                     <div id="sunkPublicationCostsContainer" class="mt-1 p-2 border border-gray-200 rounded-md bg-gray-50 min-h-[50px]">
                         <p id="noSunkCostsMessage" class="text-xs text-gray-500">No hay costos de publicación anteriores.</p>
                         <ul id="sunkCostsList" class="list-none p-0 m-0"></ul>
                         <div id="sunkCostsActions" class="mt-2 space-x-2" style="display: none;">
                             <button type="button" id="undoLastSunkCostBtn" class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-2 rounded">Eliminar Última</button>
                             <button type="button" id="clearAllSunkCostsBtn" class="text-xs bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded">Eliminar Todas</button>
                         </div>
                     </div>
                 </div>

                 <div>                       <label for="return_percentage" class="block text-sm font-medium text-gray-700">Retorno (%)</label>
                      <select id="return_percentage" name="return_percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                           <option value="36.7" selected>36.7</option>
                           <option value="43.5">43.5 (Premium)</option>
                           <option value="53.9">53.9 (Especializado)</option>
                      </select>
                 </div>
            </div>

            <div id="pricingSection" class="md:col-span-2 border p-4 rounded-md bg-gray-50" style="display: none;">
                <h3 class="text-lg font-semibold mb-2">Precios del Producto Seleccionado: <span id="selectedProductName" class="font-normal"></span></h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="product_selling_price" class="block text-sm font-medium text-gray-700">Precio de Venta del Producto Fabricado (por unidad)</label>
                        <input type="number" step="1" id="product_selling_price" name="product_selling_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="0" required min="0">
                    </div>
                    <div>
                        <label for="fabrication_cycles" class="block text-sm font-medium text-gray-700">Lotes de Fabricación</label>
                        <input type="number" step="1" id="fabrication_cycles" name="fabrication_cycles" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="1" required min="1">
                    </div>
                </div>
                <h4 class="text-md font-semibold mt-6 mb-2 border-t pt-4">Precios de Compra / Ingredientes</h4>
                <div id="ingredientPriceInputs" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4"></div>

                <div class="mt-6 text-center flex justify-center space-x-4">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Calcular</button>
                    <button type="button" id="saveProductPricesButton" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Guardar</button>
                    <button type="button" id="duplicateProductButton" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded">Duplicar</button>
                   </div>
             </div>

             <div class="md:col-span-2 border p-4 rounded-md bg-gray-50">
                 <h3 class="text-lg font-semibold mb-2">Definicion del Producto</h3>
                  <div class="grid grid-cols-1 gap-4">
                       <div> <!-- Nombre del producto ocupa toda la fila -->
                          <label for="product_name" class="block text-sm font-medium text-gray-700">Nombre del Producto</label>                           <input type="text" id="product_name" name="product_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                     </div>
                  </div>
                  <!-- Fila para Poder, Cantidad Crafteada, e Ingredientes (selector) -->
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 items-end">
                     <div>
                          <label for="object_power" class="block text-sm font-medium text-gray-700">Poder del Objeto</label>
                          <input type="number" step="1" id="object_power" name="object_power" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="0" required min="0">
                     </div>
                     <div>
                          <label for="crafted_units" class="block text-sm font-medium text-gray-700">Cantidad Crafteada</label>
                          <input type="number" step="1" id="crafted_units" name="crafted_units" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="1" required min="1">
                     </div>
                     <div>
                          <label for="ingredient_count" class="block text-sm font-medium text-gray-700">Ingredientes</label>
                          <select id="ingredient_count" name="ingredient_count" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" required>
                              <option value="1">1</option><option value="2">2</option><option value="3" selected>3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option>
                          </select>
                     </div>
                 </div>
                 <div id="ingredientInputs" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 border-t pt-4">
                                      </div>
                                    <div class="mt-6 text-center">
                       <button type="button" id="saveProductDefinitionButton" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Guardar Definicion del Producto
                       </button>
                  </div>
             </div>

        </form>

        
    </main>

    <?php // Footer y Scripts ?>
     <footer class="bg-gray-800 text-white text-center p-4 mt-8">
          <div class="container mx-auto">
              <p>&copy; <?php echo date("Y"); ?> Honoris. Todos los derechos reservados.</p>
          </div>
      </footer>

    <script src="./frontend/js/simple_ingredient_generator.js"></script>     <?php
     // Si tu calculadora usaba jQuery o cualquier otra libreria, incluyela aqui si es necesario
     ?>

</body>
</html>