document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // --- Constantes ---
    const LOCAL_STORAGE_KEY = 'craftingProductsHistory';
    const HISTORY_LIMIT = 10;

    // --- Selectores del DOM centralizados ---
    const DOM = {
        // Formulario principal y secciones
        craftingCalculatorForm: document.getElementById('craftingCalculatorForm'),
        productNameInput: document.getElementById('product_name'),
        objectPowerInput: document.getElementById('object_power'),
        ingredientCountSelect: document.getElementById('ingredient_count'),
        craftedUnitsInput: document.getElementById('crafted_units'),
        ingredientInputsDiv: document.getElementById('ingredientInputs'),
        // Seccion de precios
        pricingSectionDiv: document.getElementById('pricingSection'),
        selectedProductNameSpan: document.getElementById('selectedProductName'),
        ingredientPriceInputsDiv: document.getElementById('ingredientPriceInputs'),
        productSellingPriceInput: document.getElementById('product_selling_price'),
        fabricationCyclesInput: document.getElementById('fabrication_cycles'), // Nuevo campo
        // Botones de guardado
        saveProductDefinitionButton: document.getElementById('saveProductDefinitionButton'),
        saveProductPricesButton: document.getElementById('saveProductPricesButton'),
        duplicateProductButton: document.getElementById('duplicateProductButton'), // Nuevo boton Duplicar
        // Historial
        productHistoryDiv: document.getElementById('productHistory'),
        historyListUl: document.getElementById('historyList'),
        // Seccion de Costos de Publicacion Hundidos
        sunkPublicationCostsContainer: document.getElementById('sunkPublicationCostsContainer'),
        sunkCostsList: document.getElementById('sunkCostsList'),
        noSunkCostsMessage: document.getElementById('noSunkCostsMessage'),
        sunkCostsActions: document.getElementById('sunkCostsActions'),
        undoLastSunkCostBtn: document.getElementById('undoLastSunkCostBtn'),
        clearAllSunkCostsBtn: document.getElementById('clearAllSunkCostsBtn'),
        // Resultados y errores
        validationErrorsDiv: document.getElementById('validationErrors'),
        summaryResultsDiv: document.getElementById('summaryResults'),
        tierResultsDiv: document.getElementById('tierResults'),
        // Campos de tasas generales (ejemplos, añadir todos los necesarios para collectFormData)
        rentalCostValueInput: document.getElementById('rental_cost_value'), // Cambiado de rentalCostPercentageInput
        purchasePercentageSelect: document.getElementById('purchase_percentage'),
        salesPercentageSelect: document.getElementById('sales_percentage'),
        // publicationPercentageSelect: document.getElementById('publication_percentage'), // Eliminado, tasa fija
        returnPercentageSelect: document.getElementById('return_percentage'),
    };

    /**
     * Genera dinamicamente los inputs para Nombre y Cantidad de Ingredientes
     * basado en el valor seleccionado en ingredientCountSelect.
     */
    function generateIngredientInputs() {
        console.log('generateIngredientInputs called. Selected count:', DOM.ingredientCountSelect.value); // Para depuracion
        const newCount = parseInt(DOM.ingredientCountSelect.value, 10);

        if (isNaN(newCount) || newCount < 1 || !DOM.ingredientInputsDiv) {
            console.log('Invalid count:', newCount); // Para depuracion
            return; // No generar inputs si la cantidad es invalida
        }

        // 1. Store existing values
        const existingValues = [];
        const currentIngredientItems = DOM.ingredientInputsDiv.querySelectorAll('.ingredient-input-item');
        currentIngredientItems.forEach(item => {
            const nameInput = item.querySelector('input[type="text"]');
            const quantityInput = item.querySelector('input[type="number"]');
            if (nameInput && quantityInput) {
                existingValues.push({
                    name: nameInput.value,
                    quantity: quantityInput.value
                });
            }
        });

        // 2. Clear current inputs
        DOM.ingredientInputsDiv.innerHTML = '';

        // 3. Regenerate inputs, repopulating with stored values if available
        for (let i = 1; i <= newCount; i++) {
            const ingredientDiv = document.createElement('div');
            const existingData = existingValues[i - 1]; // Get stored data for this position

            ingredientDiv.innerHTML = `
                <div class="ingredient-input-item flex items-center space-x-2" style="margin-bottom: 10px; padding: 5px; border: 1px solid #ccc;">
                    <div style="flex-grow: 1;">
                        <label for="ingredient_name_${i}" class="sr-only">Ingrediente ${i} (Nombre)</label>
                        <input type="text" id="ingredient_name_${i}" name="ingredients[${i}][name]" placeholder="Nombre Ingrediente ${i}" class="w-full p-2 border border-gray-300 rounded-md" value="${existingData?.name || ''}" required>
                    </div>
                    <div style="width: 30%;">
                        <label for="ingredient_quantity_${i}" class="sr-only">Cantidad Ingrediente ${i}</label>
                        <input type="number" step="1" id="ingredient_quantity_${i}" name="ingredients[${i}][quantity]" placeholder="Cantidad" class="w-full p-2 border border-gray-300 rounded-md" value="${existingData?.quantity || '1'}" required min="1">
                    </div>
                </div>
            `;
            DOM.ingredientInputsDiv.appendChild(ingredientDiv);
        }
         console.log(`Generated ${newCount} ingredient input blocks, preserving existing data where possible.`); // Para depuracion
    }

    // --- Funciones de Recopilacion de Datos ---
    /**
     * Recopila los valores actuales de los inputs del formulario.
     * @returns {object} Un objeto con los valores de los inputs del formulario.
     */
    function collectFormData() {
        const formData = {};

        // Seccion 1 - Costos y Tasas Generales (Asegurate que los IDs en HTML coincidan)
        formData['rental_cost_value'] = DOM.rentalCostValueInput?.value ?? '0'; // Cambiado de rental_cost_percentage
        formData['purchase_percentage'] = DOM.purchasePercentageSelect?.value ?? '2.5';
        formData['sales_percentage'] = DOM.salesPercentageSelect?.value ?? '4';
        formData['publication_percentage'] = '2.5'; // Tasa fija
        formData['return_percentage'] = DOM.returnPercentageSelect?.value ?? '36.7';

        // Seccion 2 - Definicion del Producto (Asegurate que los IDs en HTML coincidan)
        formData['product_name'] = DOM.productNameInput?.value ?? '';
        formData['object_power'] = DOM.objectPowerInput?.value ?? '0';
        formData['ingredient_count'] = DOM.ingredientCountSelect?.value ?? '1';
        formData['crafted_units'] = DOM.craftedUnitsInput?.value ?? '1';

        // Sumar costos hundidos activos para enviar al backend
        let totalSunkCostsToApply = 0;
        const loadedProductId = DOM.craftingCalculatorForm.dataset.loadedId;
        if (loadedProductId) {
            const history = getProductHistory();
            const currentProduct = history.find(p => p.id === loadedProductId);
            if (currentProduct && currentProduct.data?.sunk_publication_costs_array) {
                totalSunkCostsToApply = currentProduct.data.sunk_publication_costs_array.reduce((sum, cost) => sum + cost, 0);
            }
        }
        formData['total_sunk_publication_cost_to_deduct'] = totalSunkCostsToApply;

        // Ingredientes Dinamicos (Nombres y Cantidades)
        formData['ingredients'] = [];
        if (DOM.ingredientInputsDiv) {
            DOM.ingredientInputsDiv.querySelectorAll('.ingredient-input-item').forEach(ingredientItemDiv => {
                 const nameInput = ingredientItemDiv.querySelector('input[type="text"]');
                 const quantityInput = ingredientItemDiv.querySelector('input[type="number"]');
                 if (nameInput && quantityInput) {
                     formData['ingredients'].push({
                         name: nameInput.value,
                         quantity: quantityInput.value
                     });
                 }
            });
        }

        // Seccion 3 - Precios (Solo si la seccion esta visible)
        formData['ingredient_prices'] = [];
        formData['fabrication_cycles'] = DOM.fabricationCyclesInput?.value ?? '1'; // Nuevo campo
         if (DOM.ingredientPriceInputsDiv) {
             DOM.ingredientPriceInputsDiv.querySelectorAll('.ingredient-price-item').forEach(div => { // Asumiendo que tambien quieres ser especifico aqui
                  const priceInput = div.querySelector('input[type="number"]');
                  const nameInputHidden = div.querySelector('input[type="hidden"]'); // Obtener el nombre del ingrediente asociado
                  if (priceInput && nameInputHidden) {
                       formData['ingredient_prices'].push({
                          name: nameInputHidden.value,
                          price: priceInput.value
                       });
                  }
             });
         } else {
              console.warn("Element 'ingredientPriceInputsDiv' not found in DOM object or page.");
         }

        // Precio de Venta del Producto (Asegurate que el ID en HTML coincida)
        formData['product_selling_price'] = DOM.productSellingPriceInput?.value ?? '0';
        
        console.log("Collected form data:", formData); // Para depuracion

        return formData;
    }

    // --- Funciones de Visualizacion de Resultados y Errores ---

    /**
     * Muestra los errores de validacion en la interfaz.
     * @param {string[]} errors - Array de mensajes de error.
     */
    function displayErrors(errors) {
         console.log("Displaying errors:", errors); // Para depuracion
         if (!DOM.validationErrorsDiv) {
             console.error("Element 'validationErrorsDiv' not found in DOM object or page.");
             return;
         }
         DOM.validationErrorsDiv.innerHTML = '';
         if (errors && errors.length > 0) {
             DOM.validationErrorsDiv.classList.add('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4');
             const errorList = document.createElement('ul');
             errorList.classList.add('list-disc', 'list-inside');
             errors.forEach(error => {
                 const li = document.createElement('li');
                 li.textContent = error;
                 errorList.appendChild(li);
             });
             DOM.validationErrorsDiv.appendChild(errorList);
         } else {
             // Limpiar estilos si no hay errores
             DOM.validationErrorsDiv.classList.remove('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4');
         }
    }

    /**
     * Muestra los resultados del calculo en la interfaz.
     * @param {object} results - Objeto con los resultados del backend.
     */
    function displayResults(results) {
         console.log("Displaying results:", results); // Para depuracion
         if (!DOM.summaryResultsDiv || !DOM.tierResultsDiv) {
              console.error("Elements 'summaryResultsDiv' or 'tierResultsDiv' not found in DOM object or page.");
             return;
         }

         DOM.summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>';
         DOM.tierResultsDiv.innerHTML = ''; // Limpiamos la seccion de tierResults ya que se fusiona

         if (results && results.summary) {
             const summary = results.summary;
             const perUnit = results.per_unit || {}; // Tomar per_unit si existe

             // Determinar color para Ingresos Totales por Venta (Neto)
             const netSalesRevenueColor = (summary.total_net_sales_revenue ?? 0) >= 0 ? 'text-green-600' : 'text-red-600';

             let summaryHtml = `
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800">
                      <p class="${netSalesRevenueColor}"><strong>Ingresos por Venta (Neto):</strong> ${formatNumber(summary.total_net_sales_revenue ?? 0)} <span class="${(summary.net_profit_loss_percentage ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}">(${formatNumber(summary.net_profit_loss_percentage ?? 0, 1)}%)</span></p>
                      <p><strong>Costo Total de Ingredientes:</strong> ${formatNumber(summary.total_ingredient_cost ?? 0)}</p>

                      <p><strong>Ingreso por Venta (Unidad):</strong> ${formatNumber(perUnit.net_selling_price ?? 0)}</p>
                      <p><strong>Costo Total de Alquiler:</strong> ${formatNumber(summary.total_rental_cost ?? 0)}</p>

                      <p><strong>Costo por Unidad Final:</strong> ${formatNumber(perUnit.total_crafting_cost ?? 0)}</p>
                      <p><strong>Costo Total de Fabricacion:</strong> ${formatNumber(summary.total_crafting_cost ?? 0)}</p>
                      <p class="md:col-span-1"><strong>Costo Publicación Actual (2.5%):</strong> ${formatNumber(summary.current_total_publication_cost ?? 0)}</p>
                      <p class="md:col-span-1"><strong>Costos Publicaciones Anteriores Aplicados:</strong> ${formatNumber(summary.total_sunk_publication_cost_applied ?? 0)}</p>
                  </div>
             `;
             DOM.summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>' + summaryHtml;
         }

         // Limpiar areas de errores si no hay resultados o si la operacion fue exitosa
          if (results && results.success === true) {
              displayErrors([]); // Limpiar errores si la operacion fue exitosa
          }

    }
    /**
      * Formatea un numero.
      * @param {number} number - El numero a formatear.
      * @param {number} [decimals=2] - Numero de decimales.
      * @param {string} [locale='es-ES'] - Localizacion.
      * @returns {string} El numero formateado.
      */
     function formatNumber(number, decimals = 2, locale = 'es-ES') {
          const num = parseFloat(number);
          if (isNaN(num)) {
              return new Intl.NumberFormat(locale, {
                   minimumFractionDigits: decimals,
                   maximumFractionDigits: decimals,
              }).format(0);
          }
          return new Intl.NumberFormat(locale, {
              minimumFractionDigits: decimals,
              maximumFractionDigits: decimals,
          }).format(num);
     }
     
    // --- Manejadores de Eventos ---
    /**
     * Maneja el envio del formulario de calculo.
     * @param {Event} event - El objeto evento del submit.
     */
    async function handleSubmitForm(event) {
        if (DOM.craftingCalculatorForm) { // Verificacion adicional
            // Guardar referencia al formulario para usarla en saveProductPricesAndResults
            DOM.craftingCalculatorForm.dataset.lastResults = null; // Limpiar resultados anteriores
             console.log("Form submit event detected!"); // Para depuracion

             event.preventDefault(); // Prevenir el envio tradicional del formulario

             // Limpiar resultados anteriores y errores
             displayErrors([]); // Limpiar errores visualmente
             displayResults(null); // Limpiar resultados visualmente
            
             // Recopilar datos del formulario (usando la funcion collectFormData que acabamos de añadir)
             const formData = collectFormData();

             // La validacion principal se hace en el backend, pero se podrian añadir validaciones basicas aqui.
             console.log("Datos del formulario a enviar para calcular:", formData); // Para depuracion


             try {
                 const response = await fetch('backend/calculate_crafting.php', {
                     method: 'POST',
                     headers: {
                          'Content-Type': 'application/json'
                     },
                     body: JSON.stringify(formData),
                 });

                  const contentType = response.headers.get("content-type");
                  if (!contentType || !contentType.includes("application/json")) {
                      const text = await response.text();
                      console.error('Respuesta no es JSON:', text);
                       displayErrors(['Error en la respuesta del servidor. Formato inesperado o error en backend.']);
                      // Mostrar la respuesta de texto en la consola para depurar el error del backend
                      console.error('Texto de respuesta del servidor:', text);
                      return;
                  }

                 const result = await response.json();
                  console.log("Resultado del backend:", result);

                 if (result.success) {
                     displayResults(result.results);
                     DOM.craftingCalculatorForm.dataset.lastResults = JSON.stringify(result.results); // Guardar resultados para el boton de guardar precios
                     displayErrors([]); // Asegurarse de limpiar errores si fue exitoso

                 } else {
                    // Si success es false, esperamos un array de errores
                     displayErrors(result.errors || [result.error || 'Ocurrio un error desconocido en el calculo.']);
                     displayResults(null); // Limpiar resultados si hubo error
                 }
             } catch (error) {
                 console.error('Error al realizar el calculo o procesar la respuesta:', error);
                 displayErrors(['Ocurrio un error al comunicarse con el servidor o procesar la respuesta. Consulta la consola para mas detalles.']);
                 displayResults(null); // Limpiar resultados si hubo error
             }
        } else {
             console.error("Error: Element 'craftingCalculatorForm' not found for submit listener.");
        }
    }

    // --- Funciones de Gestion de LocalStorage e Historial ---

    /**
     * Obtiene el historial de productos de localStorage.
     * @returns {Array<object>} Un array de objetos de productos guardados, o un array vacio si no hay.
     */
     function getProductHistory() {
        try {
            const historyJson = localStorage.getItem(LOCAL_STORAGE_KEY);
            return historyJson ? JSON.parse(historyJson) : [];
        } catch (e) {
            console.error("Error al leer de localStorage:", e);
            // Podria mostrar un mensaje de error al usuario si localStorage falla
            return [];
        }
     }

    /**
     * Guarda un array de productos en localStorage.
     * @param {Array<object>} history - El array de productos a guardar.
     */
     function saveProductHistory(history) {
        try {
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(history));
            console.log("Historial de productos guardado en localStorage:", history);
        } catch (e) {
            console.error("Error al escribir en localStorage:", e);
            alert("Error al guardar el historial de productos.");
        }
     }

      /**
       * Muestra la lista de productos guardados en el historial en la interfaz.
       */
      function displayProductHistory() {
           if (!DOM.historyListUl) {
               console.error("Element 'historyListUl' not found in DOM object or page.");
               return;
           }

           console.log("Displaying product history..."); // Para depuracion
           const history = getProductHistory();
           DOM.historyListUl.innerHTML = ''; // Limpiar lista actual

           if (history.length === 0) {
               DOM.historyListUl.innerHTML = '<li>No hay productos guardados.</li>';
               console.log("No products in history."); // Para depuracion
               return;
           }

           history.forEach(product => { // Asumiendo que product tiene product.id y product.name
               const listItem = document.createElement('li');
               // Anadimos una clase para poder usar 'closest' en el listener
               listItem.classList.add('history-item');
               // Guardamos el ID en un atributo data para poder recuperarlo facilmente
               listItem.dataset.id = product.id;

               // Estructura del elemento de la lista: Nombre + Botones
               // Las clases .load-btn y .delete-btn seran estilizadas por el CSS del PHP.
               // La clase .product-name-span tambien puede ser estilizada o usada para targeting.
               listItem.innerHTML = `
                   <span class="product-name-span" style="cursor: pointer; text-decoration: underline;">${product.displayName || product.name}</span>
                   <div class="history-actions">
                       <button type="button" class="load-btn">Cargar</button>
                       <button type="button" class="delete-btn">Eliminar</button>
                   </div>
               `;
               DOM.historyListUl.appendChild(listItem);
           });
           console.log(`Displayed ${history.length} products in history.`); // Para depuracion
       }
     /**
      * Renderiza la lista de costos de publicacion hundidos para el producto cargado.
      */
     function renderSunkCosts() {
        const loadedProductId = DOM.craftingCalculatorForm.dataset.loadedId;
        let sunkCostsArray = [];
        if (loadedProductId) {
            const history = getProductHistory();
            const product = history.find(p => p.id === loadedProductId);
            if (product && product.data?.sunk_publication_costs_array) {
                sunkCostsArray = product.data.sunk_publication_costs_array;
            }
        }

        DOM.sunkCostsList.innerHTML = '';
        if (sunkCostsArray.length > 0) {
            DOM.noSunkCostsMessage.style.display = 'none';
            DOM.sunkCostsActions.style.display = 'block';
            sunkCostsArray.forEach((cost, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'text-xs text-gray-700 py-1 px-2 border-b border-gray-100 flex justify-between items-center';
                listItem.innerHTML = `
                    <span>Publicación ${index + 1}: ${formatNumber(cost)}</span>
                    <button type="button" data-index="${index}" class="delete-sunk-cost-btn text-red-500 hover:text-red-700 text-xs ml-2">&times;</button>
                `;
                DOM.sunkCostsList.appendChild(listItem);
            });

            // Add event listeners to new delete buttons
            DOM.sunkCostsList.querySelectorAll('.delete-sunk-cost-btn').forEach(button => {
                button.addEventListener('click', handleDeleteIndividualSunkCost);
            });

        } else {
            DOM.noSunkCostsMessage.style.display = 'block';
            DOM.sunkCostsActions.style.display = 'none';
        }
     }





     /**
      * Guarda SOLO la definicion actual del producto (Nombre, Poder, Ingredientes, Cantidad a Fabricar).
      * Esto se usa con el boton en la Seccion 2.
      * No guarda precios ni resultados calculados con este boton.
      */
     function saveProductDefinition() {
         // Necesitamos referenciar productNameInput, objectPowerInput, ingredientCountSelect, craftedUnitsInput, ingredientInputsDiv
         console.log("Attempting to save product definition..."); // Para depuracion

         // Validacion basica antes de guardar
         const productName = DOM.productNameInput.value.trim();
         if (!productName) {
             alert("Ingresa un nombre para el producto antes de guardar la definicion.");
             console.warn("Save failed: Product name missing."); // Para depuracion
             return;
         }
         const objectPowerValue = parseInt(DOM.objectPowerInput.value);
          if (isNaN(objectPowerValue) || objectPowerValue <= 0) {
               alert("Ingresa un valor valido para el Poder del Objeto (mayor a 0) antes de guardar la definicion.");
               console.warn("Save failed: Object power invalid."); // Para depuracion
               return;
          }
         const craftedUnitsValue = parseInt(DOM.craftedUnitsInput.value);
          if (isNaN(craftedUnitsValue) || craftedUnitsValue <= 0) {
               alert("Ingresa una cantidad valida a fabricar antes de guardar la definicion.");
               console.warn("Save failed: Crafted units invalid."); // Para depuracion
               return;
          }


         // Recopilar solo los datos de definicion de la Seccion 2
         const definitionData = {
             product_name: productName,
             object_power: DOM.objectPowerInput.value,
             ingredient_count: DOM.ingredientCountSelect.value,
             crafted_units: DOM.craftedUnitsInput.value,
             ingredients: [] // Solo guardar la estructura de ingredientes definida en la Seccion 2
         };

         if (DOM.ingredientInputsDiv) {
            DOM.ingredientInputsDiv.querySelectorAll('.ingredient-input-item').forEach(ingredientItemDiv => {
                const nameInput = ingredientItemDiv.querySelector('input[type="text"]');
                const quantityInput = ingredientItemDiv.querySelector('input[type="number"]');
                if (nameInput && quantityInput && nameInput.value.trim() && parseInt(quantityInput.value) > 0) {
                    definitionData.ingredients.push({
                        name: nameInput.value.trim(),
                        quantity: quantityInput.value
                    });
                }
            });
         }
         

         // Validacion de ingredientes minimos para guardar la definicion
         if (definitionData.ingredients.length === 0) {
              alert("Define al menos un ingrediente con nombre y cantidad valida para guardar la definicion.");
              console.warn("Save failed: No valid ingredients defined."); // Para depuracion
              return;
         }

         let history = getProductHistory();
         let existingProductIndex = -1;

         // Intentar encontrar si ya existe un producto con este nombre para actualizarlo
         existingProductIndex = history.findIndex(product => product.name === productName);

         const productRecord = {
             // Si existe, mantenemos el mismo ID. Si no, generamos uno nuevo.
             id: existingProductIndex !== -1 ? history[existingProductIndex].id : Date.now() + Math.random().toString(16).slice(2),
             name: productName, // Nombre base para busquedas y carga de definicion
             displayName: productName, // Inicialmente el displayName es el nombre base
             data: { ...definitionData, sunk_publication_costs_array: [] } // Guardamos definicion e inicializamos array de costos hundidos
         };


         if (existingProductIndex !== -1) {
             // Actualizar la definicion del producto existente
             // Mantenemos los precios y resultados anteriores si existian en ese registro
              const existingRecord = history[existingProductIndex];
              // Combinar la nueva definicion con los precios/resultados viejos si existen
              // Aseguramos que existingRecord.data exista antes de combinar
              existingRecord.data = { ...(existingRecord.data || {}), ...definitionData }; // Sobrescribir definicion, mantener lo demas
              existingRecord.name = productName; // Actualizar nombre si cambio
              if (!existingRecord.data.sunk_publication_costs_array) { // Asegurar que el array exista
                existingRecord.data.sunk_publication_costs_array = [];
              }
              // Si el nombre base cambia, y no hay un displayName mas especifico (de guardar precios), actualizar displayName
              if (!existingRecord.displayName || existingRecord.displayName === history[existingProductIndex].name) existingRecord.displayName = productName;
              history[existingProductIndex] = existingRecord; // Reemplazar el registro
             console.log("Definicion de producto existente actualizada:", existingRecord); // Para depuracion
             alert("Definicion del producto '" + productName + "' actualizada correctamente.");

         } else {
             // Anadir el nuevo producto (solo con definicion por ahora) al inicio del historial
             history.unshift(productRecord);
             console.log("Nueva definicion de producto anadida:", productRecord); // Para depuracion
             alert("Definicion del producto '" + productName + "' guardada correctamente.");
         }

         // Limitar el tamano del historial para no saturar localStorage
         if (history.length > HISTORY_LIMIT) {
             history = history.slice(0, HISTORY_LIMIT); // Quedarse con los 'HISTORY_LIMIT' mas recientes
         }

         saveProductHistory(history);
         displayProductHistory(); // Actualizar la visualizacion del historial inmediatamente
     }

      /**
       * Guarda o actualiza los precios de ingredientes, precio de venta y los resultados calculados
       * para el producto actualmente cargado/definido.
       * Esto se usa con el boton en la Seccion 3. Requiere que un producto haya sido cargado o definido.
       */
      function saveProductPricesAndResults() {
           console.log("Attempting to save product prices and results..."); // Para depuracion

            // Obtener el nombre BASE del producto actual desde el input de la Seccion 2
           const productName = DOM.productNameInput.value.trim();

            if (!productName) {
                alert("Define o carga un producto (y asignale un nombre) primero antes de guardar precios y resultados.");
                console.warn("Save prices failed: No product name defined."); // Para depuracion
                return;
            }
            
            // Asegurarse de que la seccion de precios este visible y tenga datos
            if (!DOM.pricingSectionDiv || DOM.pricingSectionDiv.style.display === 'none' || !DOM.ingredientPriceInputsDiv || DOM.ingredientPriceInputsDiv.children.length === 0) {
                 alert("No hay precios de ingredientes definidos para guardar. Define el producto y sus ingredientes, y luego carga sus precios.");
                 console.warn("Save prices failed: Pricing section not visible or no ingredient prices."); // Para depuracion
                 return;
            }

            let history = getProductHistory();
            // Buscar el producto en el historial por su nombre actual
            let existingProductIndex = history.findIndex(p => p.name === productName);

            if (existingProductIndex === -1) {
                 alert("El producto '" + productName + "' no se encontro en el historial. Guarda su definicion primero con el otro boton.");
                 console.warn(`Save prices failed: Product '${productName}' not found in history.`); // Para depuracion
                 return;
            }

            // Recopilar datos de la Seccion 1 (tasas generales) y Seccion 3 (precios)
             const dataToSave = {
                 // Seccion 1
                 rental_cost_value: DOM.rentalCostValueInput?.value ?? '0',
                 purchase_percentage: DOM.purchasePercentageSelect?.value ?? '2.5',
                 sales_percentage: DOM.salesPercentageSelect?.value ?? '4',
                 return_percentage: DOM.returnPercentageSelect?.value ?? '36.7',
                 // Seccion 3
                 fabrication_cycles: DOM.fabricationCyclesInput?.value ?? '1',
                 ingredient_prices: [], // Se llenara con inputs de precio
                 product_selling_price: DOM.productSellingPriceInput?.value ?? '0'
             };

             if (DOM.ingredientPriceInputsDiv) {
                DOM.ingredientPriceInputsDiv.querySelectorAll('.ingredient-price-item').forEach(priceItemDiv => {
                    const priceInput = priceItemDiv.querySelector('input[type="number"]');
                    const nameInputHidden = priceItemDiv.querySelector('input[type="hidden"]'); // Obtener el nombre del ingrediente asociado
                    // Validacion basica de precios
                    if (priceInput && nameInputHidden && nameInputHidden.value.trim() && isFinite(priceInput.value) && parseFloat(priceInput.value) >= 0) {
                         dataToSave.ingredient_prices.push({
                            name: nameInputHidden.value.trim(), // Guardar nombre limpio
                            price: priceInput.value
                         });
                    } else {
                        console.warn("Skipping invalid ingredient price input:", priceItemDiv); // Para depuracion
                    }
                });
             }

            // Validar que se hayan recopilado precios si la seccion estaba visible
             if (DOM.pricingSectionDiv && DOM.pricingSectionDiv.style.display !== 'none' && dataToSave.ingredient_prices.length === 0) {
                  alert("No se encontraron precios de ingredientes validos para guardar.");
                  console.warn("Save prices failed: No valid ingredient prices collected."); // Para depuracion
                  return;
             }

            // Validar el precio de venta
             if (!isFinite(dataToSave.product_selling_price) || parseFloat(dataToSave.product_selling_price) < 0) {
                 alert("El Precio de Venta del Producto es invalido. Debe ser un numero no negativo.");
                 console.warn("Save prices failed: Product selling price invalid."); // Para depuracion
                 return;
             }

            // Calcular el costo de publicacion para ESTE evento de guardado
            const currentSellingPrice = parseFloat(dataToSave.product_selling_price) || 0;
            const currentCraftedUnits = parseInt(DOM.craftedUnitsInput?.value) || 1; // Necesitamos las unidades crafteadas de la definicion
            const FIXED_PUBLICATION_PERCENTAGE = 2.5; // Tasa Fija
            const currentFabricationCycles = parseInt(dataToSave.fabrication_cycles) || 1;

            const grossRevenueThisSaveEventOneCycle = currentSellingPrice * currentCraftedUnits;
            const publicationCostThisSaveEventOneCycle = grossRevenueThisSaveEventOneCycle * (FIXED_PUBLICATION_PERCENTAGE / 100);
            const totalPublicationCostThisSaveEvent = publicationCostThisSaveEventOneCycle * currentFabricationCycles;

            // Actualizar costos hundidos acumulados
            const existingRecordForSunkCosts = history[existingProductIndex];
            if (!existingRecordForSunkCosts.data.sunk_publication_costs_array) {
                existingRecordForSunkCosts.data.sunk_publication_costs_array = [];
            }
            existingRecordForSunkCosts.data.sunk_publication_costs_array.push(totalPublicationCostThisSaveEvent);
            dataToSave.sunk_publication_costs_array = existingRecordForSunkCosts.data.sunk_publication_costs_array; // Asegurar que dataToSave tenga el array actualizado

            // --- Generar el displayName con los resultados ---
            let displayName = productName; // Fallback al nombre base
            const lastResultsString = DOM.craftingCalculatorForm.dataset.lastResults;
            if (lastResultsString) {
                try {
                    const lastResults = JSON.parse(lastResultsString);
                    if (lastResults && lastResults.per_unit) {
                        const pu = lastResults.per_unit;
                        const costUnit = formatNumber(pu.total_crafting_cost ?? 0);
                        const incomeUnit = formatNumber(pu.net_selling_price ?? 0);
                        const profitLossPercent = formatNumber(pu.net_profit_loss_percentage ?? 0, 1); // 1 decimal para el %
                        displayName = `${productName} - C(${costUnit}) - V(${incomeUnit}) - ${profitLossPercent}%`;
                    }
                } catch (e) {
                    console.error("Error al parsear los ultimos resultados para el displayName:", e);
                }
            }

            // Actualizar el producto existente en el historial con los nuevos precios (y resultados si los anades)
            const existingRecord = history[existingProductIndex];
             // Combinar los datos de precios con la definicion existente
             // Aseguramos que existingRecord.data exista antes de combinar
             existingRecord.data = { ...(existingRecord.data || {}), ...dataToSave }; // Sobrescribir/anadir precios y tasas generales, mantener definicion
            existingRecord.displayName = displayName; // Actualizar el displayName

            history[existingProductIndex] = existingRecord; // Reemplazar el registro en el historial
            
            saveProductHistory(history); // Guardar el historial actualizado en localStorage
            displayProductHistory(); // Actualizar la visualizacion del historial para mostrar el nuevo displayName
            renderSunkCosts(); // Actualizar la lista de costos hundidos
            alert("Precios guardados para el producto '" + productName + "'. El historial mostrará el nombre detallado.");
             console.log(`Prices and results saved for product '${productName}'.`, existingRecord); // Para depuracion
      }

      /**
       * Elimina un producto especifico del historial por su ID.
       * Se llama desde el listener del historial.
       * @param {string} id - El ID del producto a eliminar.
       */
       function deleteProduct(id) {
           console.log("Attempting to delete product with ID:", id); // Para depuracion
           let history = getProductHistory();
           const initialLength = history.length;

           // Filtrar el historial para excluir el producto con el ID dado
           history = history.filter(product => product.id !== id);

           if (history.length < initialLength) {
               saveProductHistory(history); // Guardar el historial modificado
               displayProductHistory(); // Actualizar la visualizacion
               console.log(`Product with ID ${id} deleted.`); // Para depuracion
               // No alertamos al eliminar, la actualizacion de la lista ya es feedback

               // Opcional: Si el producto eliminado era el que estaba cargado en el formulario,
               // podrias limpiar el formulario o mostrar un mensaje.
               if (DOM.productNameInput && DOM.craftingCalculatorForm.dataset.loadedId === id) {
                    // Limpiar el formulario si el producto eliminado era el cargado actualmente
                    // Esto requiere una funcion para limpiar el formulario
                    // alert("El producto cargado ha sido eliminado del historial.");
                    DOM.craftingCalculatorForm.reset(); // Simple reset
                    // clearForm(); // Si tuvieras una funcion clearForm
               }
           } else {
               console.warn("Attempted to delete product with ID not found:", id); // Para depuracion
           }
       }

      /**
       * Carga los datos de un producto especifico en el formulario por su ID.
       * Se llama desde el listener del historial.
       * @param {string} id - El ID del producto a cargar.
       */
       function loadProduct(id) {
           console.log("Attempting to load product with ID:", id); // Para depuracion
           const history = getProductHistory();
           const productToLoad = history.find(product => product.id === id);

           if (productToLoad) {
               populateFormWithProductData(productToLoad.data); // Rellenar el formulario con los datos guardados
               // Opcional: Guardar el ID del producto cargado en el formulario para referencia
               if (DOM.craftingCalculatorForm) {
                    DOM.craftingCalculatorForm.dataset.loadedId = id; // Guardar el ID del producto cargado
               }
               renderSunkCosts(); // Mostrar costos hundidos del producto cargado

               console.log(`Product with ID ${id} loaded successfully.`, productToLoad.data); // Para depuracion
               // Desplazarse suavemente hacia arriba para ver el formulario
               if (DOM.craftingCalculatorForm) {
                    DOM.craftingCalculatorForm.scrollIntoView({ behavior: 'smooth' });
               }
           } else {
               console.warn("Attempted to load product with ID not found:", id); // Para depuracion
               alert("Error: Producto no encontrado en el historial.");
           }
       }
       /**
        * Rellena los campos del formulario con los datos de un producto guardado.
        * @param {object} data - El objeto de datos del formulario guardado (product.data).
        */
        function populateFormWithProductData(data) {
            if (!data) {
                console.error("No data provided to populate form."); // Para depuracion
                // Opcional: Limpiar el formulario si no hay datos
                // clearForm(); // Si tuvieras una funcion clearForm
                return;
            }

            console.log("Populating form with data:", data); // Para depuracion

            // 1. Rellenar campos generales (Seccion 1) - Asegurate que los IDs esten referenciados al inicio
            if (DOM.rentalCostValueInput) DOM.rentalCostValueInput.value = data.rental_cost_value ?? data.rental_cost_percentage ?? '0'; // Se intenta cargar rental_cost_value, con fallback a rental_cost_percentage por retrocompatibilidad con datos viejos
            if (DOM.purchasePercentageSelect) DOM.purchasePercentageSelect.value = data.purchase_percentage ?? '2.5';
            if (DOM.salesPercentageSelect) DOM.salesPercentageSelect.value = data.sales_percentage ?? '4';
            if (DOM.returnPercentageSelect) DOM.returnPercentageSelect.value = data.return_percentage ?? '36.7';
            if (DOM.fabricationCyclesInput) DOM.fabricationCyclesInput.value = data.fabrication_cycles ?? '1'; // Cargar el multiplicador

            // 2. Rellenar definicion del producto (Seccion 2) - Asegurate que los IDs esten referenciados al inicio
            if (DOM.productNameInput) DOM.productNameInput.value = data.product_name ?? '';
            if (DOM.objectPowerInput) DOM.objectPowerInput.value = data.object_power ?? '0';

            // deberia llamar a generateIngredientInputs automaticamente si esta bien configurado.
            if (DOM.ingredientCountSelect) DOM.ingredientCountSelect.value = data.ingredient_count ?? '1';
            if (DOM.craftedUnitsInput) DOM.craftedUnitsInput.value = data.crafted_units ?? '1';

            // **Importante:** Llama a generateIngredientInputs DESPUES de establecer ingredientCountSelect.value
             console.log("Calling generateIngredientInputs after setting select value..."); // Para depuracion
             generateIngredientInputs(); // Llama a la funcion para crear los inputs de ingredientes correctos

             // Usar setTimeout para dar tiempo a que el DOM se actualice DESPUES de generateIngredientInputs
             // Esto ayuda a asegurar que los inputs de ingredientes existan antes de intentar poblarlos.
              setTimeout(() => {
                  console.log("setTimeout executed after generating ingredient inputs. Populating names and quantities."); // Para depuracion

                  // Rellenar los inputs de nombre y cantidad de ingredientes que generateIngredientInputs acaba de crear
                  if (DOM.ingredientInputsDiv) {
                    DOM.ingredientInputsDiv.querySelectorAll('.ingredient-input-item').forEach((ingredientItemDiv, index) => {
                        const nameInput = ingredientItemDiv.querySelector('input[type="text"]');
                        const quantityInput = ingredientItemDiv.querySelector('input[type="number"]');
                        if (nameInput && quantityInput && data.ingredients && data.ingredients[index]) {
                           nameInput.value = data.ingredients[index].name ?? '';
                           quantityInput.value = data.ingredients[index].quantity ?? '1';
                           console.log(`Populated ingredient ${index + 1}: Name='${nameInput.value}', Quantity='${quantityInput.value}'`); // Para depuracion
                      } else {
                          // Limpiar si no hay datos correspondientes
                          if (nameInput) nameInput.value = '';
                          if (quantityInput) quantityInput.value = '1';
                          console.log(`Ingredient ${index + 1} data not found or input missing.`); // Para depuracion
                      }
                    });
                  }

                  // 3. Generar y rellenar los inputs de precios de ingredientes (Seccion 3)
                  // Usaremos los ingredientes cargados (data.ingredients) para generar los campos de precio.
                  // Tambien pasamos los precios guardados si existen (data.ingredient_prices)
                  // y el precio de venta (data.product_selling_price).
                  // generatePricingInputs tambien hace visible la seccion de precios.
                   console.log("Calling generatePricingInputs to show section and populate prices..."); // Para depuracion
                   generatePricingInputs(data.ingredients, data.ingredient_prices, data.product_selling_price, data.product_name); // Pasa precios guardados y nombre del producto

              }, 50); // Un pequeño retraso para asegurar que los inputs se han generado en el DOM
          }
          
    /**
     * Genera dinamicamente los inputs para el Precio de Compra de cada ingrediente
     * y el input para el Precio de Venta del producto final, basado en los ingredientes de un producto cargado.
     * Tambien muestra la seccion de precios.
     * @param {object[]} ingredients - Array de objetos { name: string, quantity: number } de los ingredientes.
     * @param {object[]} [ingredientPrices=[]] - Array de objetos { name: string, price: string } de precios guardados.
     * @param {string} [sellingPrice='0'] - Precio de venta guardado.
     * @param {string} [productName=''] - Nombre del producto para mostrar.
     */
     function generatePricingInputs(ingredients, ingredientPrices = [], sellingPrice = '0', productName = '') {
         console.log("Executing generatePricingInputs with ingredients:", ingredients, "prices:", ingredientPrices, "sellingPrice:", sellingPrice, "name:", productName); // Para depuracion

         if (!DOM.ingredientPriceInputsDiv || !DOM.pricingSectionDiv || !DOM.selectedProductNameSpan || !DOM.productSellingPriceInput) {
             console.error("Missing DOM elements for generatePricingInputs. Check DOM object."); // Para depuracion
             return;
         }

         DOM.ingredientPriceInputsDiv.innerHTML = ''; // Limpiar inputs de precios anteriores
         DOM.selectedProductNameSpan.textContent = productName; // Mostrar el nombre del producto

         if (!ingredients || ingredients.length === 0) {
             DOM.pricingSectionDiv.style.display = 'none'; // Ocultar si no hay ingredientes definidos
             DOM.productSellingPriceInput.value = '0'; // Resetear precio de venta
             console.log("No ingredients to generate pricing inputs for. Hiding pricing section."); // Para depuracion
             return;
         }
         DOM.pricingSectionDiv.style.display = 'block'; // Mostrar la seccion de precios si hay ingredientes

         const ingredientPricesMap = ingredientPrices.reduce((map, item) => {
             if (item.name) map[item.name] = item.price;
             return map;
         }, {});

         ingredients.forEach((ingredient, index) => {
             const priceDiv = document.createElement('div');
             // Intentar obtener el precio guardado para este ingrediente por su nombre
             const savedPrice = ingredientPricesMap[ingredient.name] ?? '0';

             priceDiv.innerHTML = `
                 <div class="ingredient-price-item">
                     <label for="ingredient_price_${index + 1}" class="sr-only">Precio Compra ${ingredient.name}</label>
                     <input type="number" step="1" id="ingredient_price_${index + 1}" name="ingredient_prices[${index}][price]" placeholder="${ingredient.name}" class="w-full p-2 border border-gray-300 rounded-md" value="${savedPrice}" required min="0">
                     <input type="hidden" name="ingredient_prices[${index}][name]" value="${ingredient.name}">
                 </div>
             `;
             DOM.ingredientPriceInputsDiv.appendChild(priceDiv);
         });

         // Rellenar el precio de venta del producto final
         DOM.productSellingPriceInput.value = sellingPrice;
         console.log(`Generated ${ingredients.length} pricing inputs. Pricing section displayed.`); // Para depuracion
    }

    /**
     * Maneja los clics en la lista del historial para cargar o eliminar productos.
     * Utiliza delegacion de eventos.
     * @param {Event} event - El objeto evento del clic.
     */
    function handleHistoryClick(event) {
        if (DOM.historyListUl) {
           console.log("Click detected in history list."); // Para depuracion
           // Buscar el elemento 'li' mas cercano que sea un item del historial
           const historyItemLi = event.target.closest('li.history-item');

           // Si el "click" no fue dentro de un item del historial valido, no hacer nada
           if (!historyItemLi) {
                console.log("Click was not on a history item or its children."); // Para depuracion
                return;
           }

           // Obtener el ID del producto desde el elemento 'li'
           const productId = historyItemLi.dataset.id;
           if (!productId) {
               console.warn("History item clicked, but no product ID found in dataset.", historyItemLi);
               return;
           }
           console.log("History item clicked with ID:", productId); // Para depuracion

           // Identificar que parte del item del historial fue clickeada
           const target = event.target;

           // Clic en el nombre (span con clase product-name-span) O en el boton de Cargar
           if (target.classList.contains('product-name-span') || target.classList.contains('load-btn')) {
                console.log("Load requested for product ID:", productId); // Para depuracion
                loadProduct(productId);
                // event.stopPropagation(); // No es estrictamente necesario aqui si no hay otros listeners conflictivos
           }
           // Clic en el boton de Eliminar (boton con clase delete-btn)
           // Asegurarse que no sea el mismo target que el load-btn si estan anidados de alguna forma (no deberian)
           else if (target.classList.contains('delete-btn')) {
                console.log("Delete requested for product ID:", productId); // Para depuracion
               if (confirm("¿Estas seguro de que quieres eliminar este producto del historial?")) {
                   deleteProduct(productId);
               }
           }
           else if (target.tagName === 'BUTTON' && target.classList.contains('delete-btn')) {
                console.log("Delete requested for product ID:", productId); // Para depuracion
               // Opcional: Pedir confirmacion antes de eliminar
               if (confirm("¿Estas seguro de que quieres eliminar este producto del historial?")) {
                   deleteProduct(productId);
               }
               // event.stopPropagation();
           } else {
                console.log("Click was on history item but not name or delete button."); // Para depuracion
           }
        } else {
             console.error("Element 'historyListUl' not found for history listener.");
        }
    }

    /**
     * Crea un nuevo registro en el historial duplicando la informacion actual del formulario.
     */
    function handleDuplicateProduct() {
        console.log("Attempting to duplicate product...");

        const productName = DOM.productNameInput.value.trim();
        if (!productName) {
            alert("Ingresa un nombre para el producto antes de duplicar.");
            console.warn("Duplicate failed: Product name missing.");
            return;
        }

        // 1. Recopilar todos los datos del formulario
        const definitionData = {
            product_name: productName,
            object_power: DOM.objectPowerInput.value,
            ingredient_count: DOM.ingredientCountSelect.value,
            crafted_units: DOM.craftedUnitsInput.value,
            ingredients: []
        };
        if (DOM.ingredientInputsDiv) {
            DOM.ingredientInputsDiv.querySelectorAll('.ingredient-input-item').forEach(item => {
                const nameInput = item.querySelector('input[type="text"]');
                const quantityInput = item.querySelector('input[type="number"]');
                if (nameInput && quantityInput && nameInput.value.trim() && parseInt(quantityInput.value) > 0) {
                    definitionData.ingredients.push({ name: nameInput.value.trim(), quantity: quantityInput.value });
                }
            });
        }
        if (definitionData.ingredients.length === 0) {
            alert("Define al menos un ingrediente válido para duplicar.");
            return;
        }

        const ratesAndPricingData = {
            rental_cost_value: DOM.rentalCostValueInput?.value ?? '0',
            purchase_percentage: DOM.purchasePercentageSelect?.value ?? '2.5',
            sales_percentage: DOM.salesPercentageSelect?.value ?? '4',
            return_percentage: DOM.returnPercentageSelect?.value ?? '36.7',
            fabrication_cycles: DOM.fabricationCyclesInput?.value ?? '1',
            ingredient_prices: [],
            product_selling_price: DOM.productSellingPriceInput?.value ?? '0',
            sunk_publication_costs_array: [] // Los costos hundidos se resetean para un duplicado
        };
        if (DOM.ingredientPriceInputsDiv && DOM.pricingSectionDiv.style.display !== 'none') {
            DOM.ingredientPriceInputsDiv.querySelectorAll('.ingredient-price-item').forEach(item => {
                const priceInput = item.querySelector('input[type="number"]');
                const nameHiddenInput = item.querySelector('input[type="hidden"]');
                if (priceInput && nameHiddenInput && isFinite(priceInput.value) && parseFloat(priceInput.value) >= 0) {
                    ratesAndPricingData.ingredient_prices.push({ name: nameHiddenInput.value, price: priceInput.value });
                }
            });
        }

        // 2. Crear el nuevo registro
        const newProductRecord = {
            id: Date.now() + Math.random().toString(16).slice(2), // Nuevo ID unico
            name: productName, // Nombre base
            displayName: productName, // DisplayName inicial
            data: { ...definitionData, ...ratesAndPricingData } // Combinar todos los datos
        };

        let history = getProductHistory();
        history.unshift(newProductRecord); // Anadir al inicio
        if (history.length > HISTORY_LIMIT) history = history.slice(0, HISTORY_LIMIT);
        saveProductHistory(history);
        displayProductHistory();
        alert(`Producto "${productName}" duplicado y guardado como una nueva entrada.`);
        console.log("Product duplicated:", newProductRecord);
    }

    function handleDeleteIndividualSunkCost(event) {
        const indexToDelete = parseInt(event.target.dataset.index, 10);
        const loadedProductId = DOM.craftingCalculatorForm.dataset.loadedId;

        if (loadedProductId && !isNaN(indexToDelete)) {
            let history = getProductHistory();
            const productIndex = history.findIndex(p => p.id === loadedProductId);
            if (productIndex !== -1 && history[productIndex].data?.sunk_publication_costs_array) {
                history[productIndex].data.sunk_publication_costs_array.splice(indexToDelete, 1);
                saveProductHistory(history);
                renderSunkCosts();
                // Opcional: Forzar un recalculo o mostrar un mensaje para recalcular
                // handleSubmitForm(new Event('submit')); // Podria ser una opcion si no es muy pesado
                alert("Costo de publicación eliminado. Por favor, recalcula para ver el impacto.");
            }
        }
    }

    function handleUndoLastSunkCost() {
        const loadedProductId = DOM.craftingCalculatorForm.dataset.loadedId;
        if (loadedProductId) {
            let history = getProductHistory();
            const productIndex = history.findIndex(p => p.id === loadedProductId);
            if (productIndex !== -1 && history[productIndex].data?.sunk_publication_costs_array?.length > 0) {
                history[productIndex].data.sunk_publication_costs_array.pop();
                saveProductHistory(history);
                renderSunkCosts();
                alert("Último costo de publicación eliminado. Por favor, recalcula.");
            } else {
                alert("No hay costos de publicación para eliminar.");
            }
        } else {
            alert("Carga un producto primero.");
        }
    }

    function handleClearAllSunkCosts() {
        const loadedProductId = DOM.craftingCalculatorForm.dataset.loadedId;
        if (loadedProductId) {
            if (confirm("¿Estás seguro de que quieres eliminar TODOS los costos de publicación para este producto?")) {
                let history = getProductHistory();
                const productIndex = history.findIndex(p => p.id === loadedProductId);
                if (productIndex !== -1 && history[productIndex].data) {
                    history[productIndex].data.sunk_publication_costs_array = [];
                    saveProductHistory(history);
                    renderSunkCosts();
                    alert("Todos los costos de publicación eliminados. Por favor, recalcula.");
                }
            }
        } else {
            alert("Carga un producto primero.");
        }
    }








    // --- Inicializacion del Script ---
    /**
     * Funcion principal de inicializacion.
     * Se ejecuta cuando el DOM esta completamente cargado.
     */
    function initializeCalculator() {
        console.log('Initializing calculator script...');

        // Verificar existencia de elementos cruciales del DOM
        if (!DOM.ingredientCountSelect || !DOM.ingredientInputsDiv || !DOM.craftingCalculatorForm || !DOM.historyListUl) {
            console.error("Error critico: Faltan elementos esenciales del DOM. El script de la calculadora no puede inicializarse completamente.");
            // Podrias mostrar un mensaje al usuario en la pagina aqui si es necesario.
            if (DOM.validationErrorsDiv) {
                displayErrors(["Error al cargar la calculadora. Faltan componentes de la pagina."]);
            }
            return; // Detener la inicializacion si faltan elementos clave
        }

        // 1. Generar los inputs de ingredientes iniciales (basado en el valor por defecto del select)
        generateIngredientInputs();

        // 2. Cargar y mostrar el historial de productos guardados
        displayProductHistory();

        // 3. Configurar Listeners de Eventos
        DOM.ingredientCountSelect.addEventListener('change', generateIngredientInputs);
        console.log('Event listener added to ingredient_count select.');

        DOM.craftingCalculatorForm.addEventListener('submit', handleSubmitForm);
        console.log('Form submit event listener added.');

        if (DOM.saveProductDefinitionButton) DOM.saveProductDefinitionButton.addEventListener('click', saveProductDefinition);
        if (DOM.saveProductPricesButton) DOM.saveProductPricesButton.addEventListener('click', saveProductPricesAndResults);
        if (DOM.historyListUl) DOM.historyListUl.addEventListener('click', handleHistoryClick);
        if (DOM.duplicateProductButton) DOM.duplicateProductButton.addEventListener('click', handleDuplicateProduct);
        // Listeners para botones de costos hundidos
        if (DOM.undoLastSunkCostBtn) DOM.undoLastSunkCostBtn.addEventListener('click', handleUndoLastSunkCost);
        if (DOM.clearAllSunkCostsBtn) DOM.clearAllSunkCostsBtn.addEventListener('click', handleClearAllSunkCosts);

        console.log('Calculator script initialized successfully.');
    }

    // Ejecutar la inicializacion
    initializeCalculator();
});