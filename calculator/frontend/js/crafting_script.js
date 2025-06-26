document.addEventListener('DOMContentLoaded', () => {
    // Referencias a elementos (select, divs, form, etc.)
    const ingredientCountSelect = document.getElementById('ingredient_count');
    const ingredientInputsDiv = document.getElementById('ingredientInputs');
    const craftingCalculatorForm = document.getElementById('craftingCalculatorForm'); // Asegurate que este aqui
    const validationErrorsDiv = document.getElementById('validationErrors'); // Asegurate que este aqui
    const summaryResultsDiv = document.getElementById('summaryResults');   // Asegurate que este aqui
    const tierResultsDiv = document.getElementById('tierResults');       // Asegurate que este aqui
    // Tambien los de la seccion 1 y 3 si vas a usar collectFormData
    const rentalCostPercentageInput = document.getElementById('rental_cost_percentage'); // etc...
    const ingredientPriceInputsDiv = document.getElementById('ingredientPriceInputs'); // Asegurate que este aqui
    const productSellingPriceInput = document.getElementById('product_selling_price'); // Asegurate que este aqui
    
    // Necesitamos referenciar productHistoryDiv, historyListUl, saveProductDefinitionButton, saveProductPricesButton
     const productHistoryDiv = document.getElementById('productHistory'); // Asegura que este aqui al inicio
     const historyListUl = document.getElementById('historyList');       // Asegura que este aqui al inicio
     const saveProductDefinitionButton = document.getElementById('saveProductDefinitionButton'); // Asegura que este aqui al inicio
     const saveProductPricesButton = document.getElementById('saveProductPricesButton'); // Asegura que este aqui al inicio

    /**
     * Genera dinamicamente los inputs para Nombre y Cantidad de Ingredientes
     * basado en el valor seleccionado en ingredientCountSelect.
     */
    function generateIngredientInputs() {
        console.log('generateIngredientInputs called. Selected count:', ingredientCountSelect.value); // Para depuracion
        const count = parseInt(ingredientCountSelect.value, 10);
        ingredientInputsDiv.innerHTML = ''; // Limpiar inputs anteriores

        if (isNaN(count) || count < 1) {
            console.log('Invalid count:', count); // Para depuracion
            return; // No generar inputs si la cantidad es invalida
        }

        for (let i = 1; i <= count; i++) {
            const ingredientDiv = document.createElement('div');
            // Nota: Simplificamos el HTML dentro para minimizar posibles errores,
            // pero mantenemos los IDs y names correctos para el backend si llegara a usarse.
            ingredientDiv.innerHTML = `
                <div style="margin-bottom: 10px; padding: 5px; border: 1px solid #ccc;">
                    <label for="ingredient_name_${i}" style="display: block; margin-bottom: 5px;">Ingrediente ${i} (Nombre)</label>
                    <input type="text" id="ingredient_name_${i}" name="ingredients[${i}][name]" style="width: 100%; padding: 5px;" required>
                    <label for="ingredient_quantity_${i}" style="display: block; margin-top: 10px; margin-bottom: 5px;">Ingrediente ${i} (Cantidad por Producto)</label>
                    <input type="number" step="1" id="ingredient_quantity_${i}" name="ingredients[${i}][quantity]" style="width: 100%; padding: 5px;" value="1" required min="1">
                </div>
            `;
            ingredientInputsDiv.appendChild(ingredientDiv);
        }
         console.log(`Generated ${count} ingredient input blocks.`); // Para depuracion
    }

    // --- Inicializar ---
    // Asegurarse de que los elementos HTML existan antes de intentar usarlos
    if (ingredientCountSelect && ingredientInputsDiv) {
        console.log('Elements found. Initializing script.'); // Para depuracion
        // 1. Generar los inputs de ingredientes iniciales (basado en el valor por defecto del select)
        generateIngredientInputs();

        // 2. Listener para el cambio en la cantidad de ingredientes (para regenerar inputs)
        ingredientCountSelect.addEventListener('change', generateIngredientInputs);
        console.log('Event listener added to ingredient_count select.'); // Para depuracion

    } else {
        console.error("Error: No se encontraron los elementos 'ingredient_count' o 'ingredientInputs' en la pagina.");
    }
    
    // --- Funciones de Recopilacion de Datos ---

    /**
     * Guarda los valores actuales de los inputs del formulario.
     * Adaptado para la calculadora de crafting.
     * @returns {object} Un objeto con los valores de los inputs del formulario.
     */
    function collectFormData() {
        const formData = {};

        // Seccion 1 - Costos y Tasas Generales (Asegurate que los IDs en HTML coincidan)
        formData['rental_cost_percentage'] = document.getElementById('rental_cost_percentage')?.value ?? '0';
        formData['purchase_percentage'] = document.getElementById('purchase_percentage')?.value ?? '2.5';
        formData['sales_percentage'] = document.getElementById('sales_percentage')?.value ?? '4';
        formData['publication_percentage'] = document.getElementById('publication_percentage')?.value ?? '2.5';
        formData['return_percentage'] = document.getElementById('return_percentage')?.value ?? '36.7';

        // Seccion 2 - Definicion del Producto (Asegurate que los IDs en HTML coincidan)
        formData['product_name'] = document.getElementById('product_name')?.value ?? '';
        formData['object_power'] = document.getElementById('object_power')?.value ?? '0';
        formData['ingredient_count'] = document.getElementById('ingredient_count')?.value ?? '1';
        formData['crafted_units'] = document.getElementById('crafted_units')?.value ?? '1';

        // Ingredientes Dinamicos (Nombres y Cantidades)
        formData['ingredients'] = [];
        // Usamos ingredientInputsDiv que ya referenciamos
        ingredientInputsDiv.querySelectorAll('div').forEach(div => {
             const nameInput = div.querySelector('input[type="text"]');
             const quantityInput = div.querySelector('input[type="number"]');
             if (nameInput && quantityInput) {
                 formData['ingredients'].push({
                     name: nameInput.value,
                     quantity: quantityInput.value
                 });
             }
        });

        // Seccion 3 - Precios (Solo si la seccion esta visible)
        formData['ingredient_prices'] = [];
         // Recopila los precios de los ingredientes tal como estan en los inputs dinamicos de la seccion de precios
         // Necesitamos referenciar ingredientPriceInputsDiv - aseguremos que este ID exista en el HTML
         const ingredientPriceInputsDiv = document.getElementById('ingredientPriceInputs');
         if (ingredientPriceInputsDiv) {
             ingredientPriceInputsDiv.querySelectorAll('div').forEach(div => {
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
              console.warn("Element with ID 'ingredientPriceInputs' not found."); // Para depuracion
         }


        // Precio de Venta del Producto (Asegurate que el ID en HTML coincida)
        formData['product_selling_price'] = document.getElementById('product_selling_price')?.value ?? '0';


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
         if (!validationErrorsDiv) {
             console.error("Element with ID 'validationErrors' not found.");
             return;
         }
         validationErrorsDiv.innerHTML = '';
         if (errors && errors.length > 0) {
             validationErrorsDiv.classList.add('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4');
             const errorList = document.createElement('ul');
             errorList.classList.add('list-disc', 'list-inside');
             errors.forEach(error => {
                 const li = document.createElement('li');
                 li.textContent = error;
                 errorList.appendChild(li);
             });
             validationErrorsDiv.appendChild(errorList);
         } else {
             // Limpiar estilos si no hay errores
             validationErrorsDiv.classList.remove('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4');
         }
    }

    /**
     * Muestra los resultados del calculo en la interfaz.
     * @param {object} results - Objeto con los resultados del backend.
     */
    function displayResults(results) {
         console.log("Displaying results:", results); // Para depuracion
         if (!summaryResultsDiv || !tierResultsDiv) {
              console.error("Elements with IDs 'summaryResults' or 'tierResults' not found.");
             return;
         }

         summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>';
         tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Unidad y Totales</h2>';


         if (results && results.summary) {
             const summary = results.summary;
             let summaryHtml = `
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800">
                      <p><strong>Costo Total de Ingredientes:</strong> ${formatNumber(summary.total_ingredient_cost ?? 0)}</p>
                      <p><strong>Costo Total de Alquiler:</strong> ${formatNumber(summary.total_rental_cost ?? 0)}</p>
                       <p><strong>Costo Total de Fabricacion (Materiales + Alquiler):</strong> ${formatNumber(summary.total_crafting_cost ?? 0)}</p>
                       <p><strong>Ingresos Totales por Venta:</strong> ${formatNumber(summary.total_sales_revenue ?? 0)}</p>
                       <p><strong>Costo Total de Publicacion:</strong> ${formatNumber(summary.total_publication_cost ?? 0)}</p>
                      <p class="${(summary.net_profit_loss ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/Perdida Neta Total:</strong> ${formatNumber(summary.net_profit_loss ?? 0)}</p>
                      <p class="${(summary.net_profit_loss_percentage ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/Perdida Neta Total (%):</strong> ${formatNumber(summary.net_profit_loss_percentage ?? 0, 2)} %</p>
                  </div>
             `;
             summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>' + summaryHtml;
         }

         // Resultados por unidad
         if (results && results.per_unit) {
              const perUnit = results.per_unit;
              let perUnitHtml = `
                   <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 mt-4">
                       <p><strong>Costo por Unidad (Materiales):</strong> ${formatNumber(perUnit.ingredient_cost ?? 0)}</p>
                       <p><strong>Costo por Unidad (Alquiler):</strong> ${formatNumber(perUnit.rental_cost ?? 0)}</p>
                        <p><strong>Costo por Unidad (Fabricacion Total):</strong> ${formatNumber(perUnit.total_crafting_cost ?? 0)}</p>
                        <p><strong>Precio de Venta Neto por Unidad:</strong> ${formatNumber(perUnit.net_selling_price ?? 0)}</p>
                       <p class="${(perUnit.net_profit_loss ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/Perdida Neta por Unidad:</strong> ${formatNumber(perUnit.net_profit_loss ?? 0)}</p>
                       <p class="${(perUnit.net_profit_loss_percentage ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/Perdida Neta por Unidad (%):</strong> ${formatNumber(perUnit.net_profit_loss_percentage ?? 0, 2)} %</p>
                   </div>
             `;
               tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Unidad</h2>' + perUnitHtml;
         }

         // Limpiar areas de errores si no hay resultados o si la operacion fue exitosa
          if (results && results.success === true) {
              displayErrors([]); // Limpiar errores si la operacion fue exitosa
          }

    }

    // Necesitamos la funcion formatNumber para displayResults
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
     
     // --- Event Listener para el envio del formulario (calcular) ---

    // Asegurate que craftingCalculatorForm este definido arriba
    if (craftingCalculatorForm) { // Verificacion adicional
         craftingCalculatorForm.addEventListener('submit', async (event) => {
             console.log("Form submit event detected!"); // Para depuracion

             event.preventDefault(); // Prevenir el envio tradicional del formulario

             // Limpiar resultados anteriores y errores
             displayErrors([]); // Limpiar errores visualmente
             displayResults(null); // Limpiar resultados visualmente


             // Recopilar datos del formulario (usando la funcion collectFormData que acabamos de añadir)
             const formData = collectFormData();

             // VALIDACION BASICA DEL FRONENT (puedes anadir mas si es necesario)
             // Esta validacion basica ya esta en la funcion collectFormData y displayErrors,
             // pero podrias anadir mas aqui si necesitas detener el envio antes de fetch.
             // Sin embargo, la validacion fuerte y completa debe estar en el backend.

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
         });
         console.log('Form submit event listener added.'); // Para depuracion
    } else {
         console.error("Error: Element with ID 'craftingCalculatorForm' not found for submit listener."); // Para depuracion
    }


// --- Datos base (Asegurate que estas constantes existan al inicio del script) ---
    const LOCAL_STORAGE_KEY = 'craftingProductsHistory'; // Asegura que este definida al inicio
    const HISTORY_LIMIT = 10; // Asegura que este definida al inicio


    // --- Funciones de Gestion de LocalStorage e Historial ---

    /**
     * Obtiene el historial de productos de localStorage.
     * @returns {Array<object>} Un array de objetos de productos guardados, o un array vacio si no hay.
     */
     function getProductHistory() {
        try {
            const historyJson = localStorage.getItem(LOCAL_STORAGE_KEY);
            // console.log("Retrieved history from localStorage:", historyJson); // Para depuracion
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
          // Necesitamos referenciar historyListUl
           const historyListUl = document.getElementById('historyList'); // Asegura que este ID exista en HTML
           if (!historyListUl) {
               console.error("Element with ID 'historyList' not found.");
               return;
           }

           console.log("Displaying product history..."); // Para depuracion
           const history = getProductHistory();
           historyListUl.innerHTML = ''; // Limpiar lista actual

           if (history.length === 0) {
               historyListUl.innerHTML = '<li>No hay productos guardados.</li>';
               console.log("No products in history."); // Para depuracion
               return;
           }

           history.forEach(product => {
               const listItem = document.createElement('li');
               // Anadimos una clase para poder usar 'closest' en el listener
               listItem.classList.add('history-item');
               // Guardamos el ID en un atributo data para poder recuperarlo facilmente
               listItem.dataset.id = product.id;

               // Estructura del elemento de la lista: Nombre + Botones
               listItem.innerHTML = `
                   <span class="product-name-span">${product.name}</span>
                   <div class="history-actions">
                       <button type="button" class="load-btn">Cargar</button>
                       <button type="button" class="delete-btn">Eliminar</button>
                   </div>
               `;
               historyListUl.appendChild(listItem);
           });
           console.log(`Displayed ${history.length} products in history.`); // Para depuracion

       }


     /**
      * Guarda SOLO la definicion actual del producto (Nombre, Poder, Ingredientes, Cantidad a Fabricar).
      * Esto se usa con el boton en la Seccion 2.
      * No guarda precios ni resultados calculados con este boton.
      */
     function saveProductDefinition() {
         // Necesitamos referenciar productNameInput, objectPowerInput, ingredientCountSelect, craftedUnitsInput, ingredientInputsDiv
         const productNameInput = document.getElementById('product_name'); // Asegura que este aqui al inicio
         const objectPowerInput = document.getElementById('object_power'); // Asegura que este aqui al inicio
         const ingredientCountSelect = document.getElementById('ingredient_count'); // Asegura que este aqui al inicio
         const craftedUnitsInput = document.getElementById('crafted_units'); // Asegura que este aqui al inicio
         // ingredientInputsDiv ya esta referenciado si seguiste el paso anterior


         console.log("Attempting to save product definition..."); // Para depuracion

         // Validacion basica antes de guardar
         const productName = productNameInput.value.trim();
         if (!productName) {
             alert("Ingresa un nombre para el producto antes de guardar la definicion.");
             console.warn("Save failed: Product name missing."); // Para depuracion
             return;
         }
         const objectPowerValue = parseInt(objectPowerInput.value);
          if (isNaN(objectPowerValue) || objectPowerValue <= 0) {
               alert("Ingresa un valor valido para el Poder del Objeto (mayor a 0) antes de guardar la definicion.");
               console.warn("Save failed: Object power invalid."); // Para depuracion
               return;
          }
         const craftedUnitsValue = parseInt(craftedUnitsInput.value);
          if (isNaN(craftedUnitsValue) || craftedUnitsValue <= 0) {
               alert("Ingresa una cantidad valida a fabricar antes de guardar la definicion.");
               console.warn("Save failed: Crafted units invalid."); // Para depuracion
               return;
          }


         // Recopilar solo los datos de definicion de la Seccion 2
         const definitionData = {
             product_name: productName,
             object_power: objectPowerInput.value,
             ingredient_count: ingredientCountSelect.value,
             crafted_units: craftedUnitsInput.value,
             ingredients: [] // Solo guardar la estructura de ingredientes definida en la Seccion 2
         };

         // Asegurate que ingredientInputsDiv este referenciado
         ingredientInputsDiv.querySelectorAll('div').forEach(div => {
              const nameInput = div.querySelector('input[type="text"]');
              const quantityInput = div.querySelector('input[type="number"]');
              if (nameInput && quantityInput && nameInput.value.trim() && parseInt(quantityInput.value) > 0) {
                  definitionData.ingredients.push({
                      name: nameInput.value.trim(),
                      quantity: quantityInput.value
                  });
              }
         });

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
             name: productName,
             data: definitionData // Guardamos SOLO la definicion del producto aqui
             // NOTA: Los precios y resultados NO se guardan con este boton, solo la definicion
         };

         if (existingProductIndex !== -1) {
             // Actualizar la definicion del producto existente
             // Mantenemos los precios y resultados anteriores si existian en ese registro
              const existingRecord = history[existingProductIndex];
              // Combinar la nueva definicion con los precios/resultados viejos si existen
              // Aseguramos que existingRecord.data exista antes de combinar
              existingRecord.data = { ...(existingRecord.data || {}), ...definitionData }; // Sobrescribir definicion, mantener lo demas
              existingRecord.name = productName; // Actualizar nombre si cambio
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
           // Necesitamos referenciar productNameInput y pricingSectionDiv, productSellingPriceInput, ingredientPriceInputsDiv
           const productNameInput = document.getElementById('product_name'); // Asegura que este aqui al inicio
           const pricingSectionDiv = document.getElementById('pricingSection'); // Asegura que este aqui al inicio
           const productSellingPriceInput = document.getElementById('product_selling_price'); // Asegura que este aqui al inicio
           const ingredientPriceInputsDiv = document.getElementById('ingredientPriceInputs'); // Asegura que este aqui al inicio


           console.log("Attempting to save product prices and results..."); // Para depuracion

            // Obtener el nombre del producto actual desde el input de la Seccion 2
           const productName = productNameInput.value.trim();

            if (!productName) {
                alert("Define o carga un producto primero antes de guardar precios y resultados.");
                console.warn("Save prices failed: No product name defined."); // Para depuracion
                return;
            }

            // Asegurarse de que la seccion de precios este visible y tenga datos
            if (pricingSectionDiv.style.display === 'none' || ingredientPriceInputsDiv.children.length === 0) {
                 alert("No hay precios de ingredientes definidos para guardar. Define el producto y sus ingredientes, y luego carga sus precios.");
                 console.warn("Save prices failed: Pricing section not visible or no ingredient prices."); // Para depuracion
                 return;
            }


            let history = getProductHistory();
            // Buscar el producto en el historial por su nombre actual
            let existingProductIndex = history.findIndex(product => product.name === productName);

            if (existingProductIndex === -1) {
                 alert("El producto '" + productName + "' no se encontro en el historial. Guarda su definicion primero con el otro boton.");
                 console.warn(`Save prices failed: Product '${productName}' not found in history.`); // Para depuracion
                 return;
            }

            // Recopilar solo los datos de precios y el precio de venta (Seccion 3)
             const pricingData = {
                 ingredient_prices: [], // Se llenara con inputs de precio
                 product_selling_price: productSellingPriceInput.value
             };

             // Asegurate que ingredientPriceInputsDiv este referenciado
             ingredientPriceInputsDiv.querySelectorAll('div').forEach(div => {
                  const priceInput = div.querySelector('input[type="number"]');
                  const nameInputHidden = div.querySelector('input[type="hidden"]'); // Obtener el nombre del ingrediente asociado
                  // Validacion basica de precios
                  if (priceInput && nameInputHidden && nameInputHidden.value.trim() && isFinite(priceInput.value) && parseFloat(priceInput.value) >= 0) {
                       pricingData.ingredient_prices.push({
                          name: nameInputHidden.value.trim(), // Guardar nombre limpio
                          price: priceInput.value
                       });
                  } else {
                      console.warn("Skipping invalid ingredient price input:", div); // Para depuracion
                  }
             });

            // Validar que se hayan recopilado precios si la seccion estaba visible
             if (pricingSectionDiv.style.display !== 'none' && pricingData.ingredient_prices.length === 0) {
                  alert("No se encontraron precios de ingredientes validos para guardar.");
                  console.warn("Save prices failed: No valid ingredient prices collected."); // Para depuracion
                  return;
             }

            // Validar el precio de venta
             if (!isFinite(pricingData.product_selling_price) || parseFloat(pricingData.product_selling_price) < 0) {
                 alert("El Precio de Venta del Producto es invalido. Debe ser un numero no negativo.");
                 console.warn("Save prices failed: Product selling price invalid."); // Para depuracion
                 return;
             }


            // --- Opcional: Recopilar los resultados calculados si estan visibles ---
            // Esto es si quieres persistir los ULTIMOS resultados calculados.
            // Podrias copiarlos de los innerHTML de summaryResultsDiv y tierResultsDiv
            // o guardarlos en variables globales si ya se calcularon.
            // Por ahora, no guardaremos resultados calculados, solo los precios.
            // Los resultados se recalcularan al cargar o al hacer submit.


            // Actualizar el producto existente en el historial con los nuevos precios (y resultados si los anades)
            const existingRecord = history[existingProductIndex];
             // Combinar los datos de precios con la definicion existente
             // Aseguramos que existingRecord.data exista antes de combinar
             existingRecord.data = { ...(existingRecord.data || {}), ...pricingData }; // Sobrescribir precios y venta, mantener definicion
            // No actualizamos el nombre aqui, ya fue definido y encontrado por ese nombre.

            history[existingProductIndex] = existingRecord; // Reemplazar el registro en el historial

            saveProductHistory(history); // Guardar el historial actualizado en localStorage
            displayProductHistory(); // Actualizar la visualizacion del historial (no cambiara visualmente, pero es buena practica)
            alert("Precios y resultados guardados para el producto '" + productName + "'.");
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
                const productNameInput = document.getElementById('product_name');
               if (productNameInput && productNameInput.dataset.loadedId === id) {
                    // Limpiar el formulario si el producto eliminado era el cargado actualmente
                    // Esto requiere una funcion para limpiar el formulario
                    // alert("El producto cargado ha sido eliminado del historial.");
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
                const craftingCalculatorForm = document.getElementById('craftingCalculatorForm');
               if (craftingCalculatorForm) {
                    craftingCalculatorForm.dataset.loadedId = id; // Guardar el ID del producto cargado
               }

               console.log(`Product with ID ${id} loaded successfully.`, productToLoad.data); // Para depuracion
               // Desplazarse suavemente hacia arriba para ver el formulario
               const formElement = document.getElementById('craftingCalculatorForm');
               if (formElement) {
                    formElement.scrollIntoView({ behavior: 'smooth' });
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
            const rentalCostPercentageInput = document.getElementById('rental_cost_percentage');
            const purchasePercentageSelect = document.getElementById('purchase_percentage');
            const salesPercentageSelect = document.getElementById('sales_percentage');
            const publicationPercentageSelect = document.getElementById('publication_percentage');
            const returnPercentageSelect = document.getElementById('return_percentage');

            if (rentalCostPercentageInput) rentalCostPercentageInput.value = data.rental_cost_percentage ?? '0';
            if (purchasePercentageSelect) purchasePercentageSelect.value = data.purchase_percentage ?? '2.5';
            if (salesPercentageSelect) salesPercentageSelect.value = data.sales_percentage ?? '4';
            if (publicationPercentageSelect) publicationPercentageSelect.value = data.publication_percentage ?? '2.5';
            if (returnPercentageSelect) returnPercentageSelect.value = data.return_percentage ?? '36.7';


            // 2. Rellenar definicion del producto (Seccion 2) - Asegurate que los IDs esten referenciados al inicio
            const productNameInput = document.getElementById('product_name');
            const objectPowerInput = document.getElementById('object_power');
            const ingredientCountSelect = document.getElementById('ingredient_count');
            const craftedUnitsInput = document.getElementById('crafted_units');
            // ingredientInputsDiv ya esta referenciado

            if (productNameInput) productNameInput.value = data.product_name ?? '';
            if (objectPowerInput) objectPowerInput.value = data.object_power ?? '0';
            // NOTA: Al cambiar ingredientCountSelect.value, el listener de 'change'
            // deberia llamar a generateIngredientInputs automaticamente si esta bien configurado.
            // Si no, tendrias que llamarlo explicitamente aqui.
            if (ingredientCountSelect) ingredientCountSelect.value = data.ingredient_count ?? '1';
            if (craftedUnitsInput) craftedUnitsInput.value = data.crafted_units ?? '1';

            // **Importante:** Llama a generateIngredientInputs DESPUES de establecer ingredientCountSelect.value
             console.log("Calling generateIngredientInputs after setting select value..."); // Para depuracion
             generateIngredientInputs(); // Llama a la funcion para crear los inputs de ingredientes correctos

             // Usar setTimeout para dar tiempo a que el DOM se actualice DESPUES de generateIngredientInputs
              setTimeout(() => {
                  console.log("setTimeout executed after generating ingredient inputs. Populating names and quantities."); // Para depuracion

                  // Rellenar los inputs de nombre y cantidad de ingredientes que generateIngredientInputs acaba de crear
                  // Asegurate que ingredientInputsDiv este referenciado
                  ingredientInputsDiv.querySelectorAll('div').forEach((div, index) => {
                      const nameInput = div.querySelector('input[type="text"]');
                      const quantityInput = div.querySelector('input[type="number"]');
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

                  // 3. Generar y rellenar los inputs de precios de ingredientes (Seccion 3)
                  // Usaremos los ingredientes cargados (data.ingredients) para generar los campos de precio.
                  // Tambien pasamos los precios guardados si existen (data.ingredient_prices)
                  // y el precio de venta (data.product_selling_price).
                  // generatePricingInputs tambien hace visible la seccion de precios.
                   console.log("Calling generatePricingInputs to show section and populate prices..."); // Para depuracion
                   generatePricingInputs(data.ingredients, data.ingredient_prices, data.product_selling_price, data.product_name); // Pasa precios guardados y nombre del producto

                  // La funcion generatePricingInputs ya se encarga de mostrar la seccion.

              }, 50); // Un pequeño retraso para asegurar que los inputs se han generado en el DOM

          }


    // Necesitamos la funcion generatePricingInputs para populateFormWithProductData y saveProductPricesAndResults
    // Asegurate que el codigo de generatePricingInputs este pegado antes de populateFormWithProductData

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
         // Necesitamos referenciar ingredientPriceInputsDiv, pricingSectionDiv, selectedProductNameSpan, productSellingPriceInput
          const ingredientPriceInputsDiv = document.getElementById('ingredientPriceInputs'); // Asegura que este aqui al inicio
          const pricingSectionDiv = document.getElementById('pricingSection'); // Asegura que este aqui al inicio
          const selectedProductNameSpan = document.getElementById('selectedProductName'); // Asegura que este aqui al inicio
          const productSellingPriceInput = document.getElementById('product_selling_price'); // Asegura que este aqui al inicio


         console.log("Executing generatePricingInputs with ingredients:", ingredients, "prices:", ingredientPrices, "sellingPrice:", sellingPrice, "name:", productName); // Para depuracion

         if (!ingredientPriceInputsDiv || !pricingSectionDiv || !selectedProductNameSpan || !productSellingPriceInput) {
             console.error("Missing elements for generatePricingInputs."); // Para depuracion
             return;
         }

         ingredientPriceInputsDiv.innerHTML = ''; // Limpiar inputs de precios anteriores
         selectedProductNameSpan.textContent = productName; // Mostrar el nombre del producto

         if (!ingredients || ingredients.length === 0) {
             pricingSectionDiv.style.display = 'none'; // Ocultar si no hay ingredientes definidos
             productSellingPriceInput.value = '0'; // Resetear precio de venta
             console.log("No ingredients to generate pricing inputs for. Hiding pricing section."); // Para depuracion
             return;
         }

         pricingSectionDiv.style.display = 'block'; // Mostrar la seccion de precios si hay ingredientes

         const ingredientPricesMap = ingredientPrices.reduce((map, item) => {
             if (item.name) map[item.name] = item.price;
             return map;
         }, {});

         ingredients.forEach((ingredient, index) => {
             const priceDiv = document.createElement('div');
             // Intentar obtener el precio guardado para este ingrediente por su nombre
             const savedPrice = ingredientPricesMap[ingredient.name] ?? '0';

             priceDiv.innerHTML = `
                 <div>
                     <label for="ingredient_price_${index + 1}" class="block text-sm font-medium text-gray-700">Precio Compra "${ingredient.name}" (por unidad)</label>
                     <input type="number" step="1" id="ingredient_price_${index + 1}" name="ingredient_prices[${index}][price]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2" value="${savedPrice}" required min="0">
                     <input type="hidden" name="ingredient_prices[${index}][name]" value="${ingredient.name}"> </div>
             `;
             ingredientPriceInputsDiv.appendChild(priceDiv);
         });

         // Rellenar el precio de venta del producto final
         productSellingPriceInput.value = sellingPrice;
         console.log(`Generated ${ingredients.length} pricing inputs. Pricing section displayed.`); // Para depuracion
}


    // Listener para el boton "Guardar Definicion del Producto" (en Seccion 2)
    if (saveProductDefinitionButton) {
         saveProductDefinitionButton.addEventListener('click', saveProductDefinition);
         console.log('Event listener added to saveProductDefinitionButton.'); // Para depuracion
    } else {
         console.error("Element with ID 'saveProductDefinitionButton' not found."); // Para depuracion
    }


    // Listener para el boton "Guardar Precios y Resultados" (en Seccion 3)
    if (saveProductPricesButton) {
        saveProductPricesButton.addEventListener('click', saveProductPricesAndResults);
        console.log('Event listener added to saveProductPricesButton.'); // Para depuracion
    } else {
         console.error("Element with ID 'saveProductPricesButton' not found."); // Para depuracion
    }


    // Listener para los clicks en la lista del historial (cargar y eliminar productos)
    if (historyListUl) {
       historyListUl.addEventListener('click', (event) => {
           console.log("Click detected in history list."); // Para depuracion
           // Buscar el elemento 'li' mas cercano que sea un item del historial
           const historyItemLi = event.target.closest('li.history-item');

           // Si el "click" no fue dentro de un item del historial valido, no hacer nada
           if (!historyItemLi) {
                console.log("Click was not on a history item."); // Para depuracion
                return;
           }

           // Obtener el ID del producto desde el elemento 'li'
           const productId = historyItemLi.dataset.id;
           console.log("History item clicked with ID:", productId); // Para depuracion


           // Identificar que parte del item del historial fue clickeada
           const target = event.target;

           // Clic en el nombre (span con clase product-name-span) para cargar
           if (target.classList.contains('product-name-span')) { // Usamos la clase para ser mas especificos
                console.log("Load requested for product ID:", productId); // Para depuracion
                loadProduct(productId);
                event.stopPropagation(); // Detener la propagacion del evento para evitar que se active algo mas

           }
           // Clic en el boton de Eliminar (boton con clase delete-btn)
           else if (target.tagName === 'BUTTON' && target.classList.contains('delete-btn')) {
                console.log("Delete requested for product ID:", productId); // Para depuracion
               // Opcional: Pedir confirmacion antes de eliminar
               // if (confirm("¿Estás seguro de que quieres eliminar este producto del historial?")) {
                   deleteProduct(productId);
               // }
               event.stopPropagation(); // Detener la propagacion del evento

           } else {
                console.log("Click was on history item but not name or delete button."); // Para depuracion
           }
       });
       console.log('Event listener added to historyListUl.'); // Para depuracion
    } else {
         console.error("Element with ID 'historyListUl' not found for history listener."); // Para depuracion
    }


    // NOTA: El listener para el 'change' del selector ingredient_count y la llamada
    // inicial a generateIngredientInputs, asi como el listener para el submit
    // del formulario ya deben estar en tu archivo.

});