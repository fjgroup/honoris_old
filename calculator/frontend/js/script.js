document.addEventListener('DOMContentLoaded', () => {
    // Definiciones de datos (anteriormente en PHP)
    const recipesJS = {
        't3': { prev_tier: 't2', req_prev: 1, req_raw: 2 },
        't4': { prev_tier: 't3', req_prev: 1, req_raw: 2 },
        't5': { prev_tier: 't4', req_prev: 1, req_raw: 3 },
        't6': { prev_tier: 't5', req_prev: 1, req_raw: 4 },
        't7': { prev_tier: 't6', req_prev: 1, req_raw: 5 },
        't8': { prev_tier: 't7', req_prev: 1, req_raw: 5 },
        't2': { prev_tier: null, req_prev: 0, req_raw: 1 },
    };

    const buildingValuesJS = {
        't2': 0,
        't3': 8,
        't4': 16,
        't5': 32,
        't6': 64,
        't7': 128,
        't8': 256,
    };

    // Elementos del formulario
    const calculatorForm = document.getElementById('calculatorForm');
    const validationErrorsDiv = document.getElementById('validationErrors');
    const summaryResultsDiv = document.getElementById('summaryResults');
    const tierResultsDiv = document.getElementById('tierResults');

    const startingTierSelect = document.getElementById('starting_tier');
    const craftingLimitTierSelect = document.getElementById('crafting_limit_tier');
    const initialHidesInput = document.getElementById('initial_hides');
    const labelInitialHides = document.getElementById('label_initial_hides');
    const buyingPriceSectionDiv = document.getElementById('buyingPriceSection');
    const buyingPriceInputDiv = document.getElementById('buyingPriceInput');
    const sellingPricesInputsDiv = document.getElementById('sellingPricesInputs');
    const rawHideCostsInputsDiv = document.getElementById('rawHideCostsInputs'); // <-- DEBE SER 'rawHideCostsInputs'

    // Bot\u00f3n "Guardar C\u00e1lculo" (tiene el ID loadCalculationButton en tu HTML)
    const saveCalculationButton = document.getElementById('loadCalculationButton'); // <-- Referencia al bot\u00f3n Guardar

    // \u00c1rea y lista para mostrar el historial (elementos a\u00f1adidos en el HTML)
    const calculationHistoryDiv = document.getElementById('calculationHistory'); // <-- Nuevo div contenedor
    const historyListUl = document.getElementById('historyList'); // <-- Nueva lista ul

    // Datos base
    const allTiers = ['t2', 't3', 't4', 't5', 't6', 't7', 't8'];
    const availableStartTiers = allTiers.slice(0, -1);
    const availableLimitTiers = allTiers;

    // Valores por defecto (si no hay valores guardados)
    const defaultSellingPrices = { t2: 0, t3: 0, t4: 0, t5: 0, t6: 0, t7: 0, t8: 0 };
    const defaultRawHideCosts = { t2: 0, t3: 0, t4: 0, t5: 0, t6: 0, t7: 0, t8: 0 };
    const defaultBuyingPrices = { t2: 0, t3: 0, t4: 0, t5: 0, t6: 0, t7: 0 };

    const LOCAL_STORAGE_KEY = 'leatherCalculationsHistory'; // Clave para localStorage
    const HISTORY_LIMIT = 10; // Limite de c\u00e1lculos en el historial


    // --- Definici\u00f3n de Funciones ---

    /**
     * Helper function to get the previous tier string.
     * @param {string} tier - The current tier (e.g., 't3').
     * @returns {string|null} The previous tier string (e.g., 't2') or null if it's t2.
     */
    function getPrevTier(tier) {
        const index = allTiers.indexOf(tier);
        if (index > 0) {
            return allTiers[index - 1];
        }
        return null;
    }

    /**
     * Guarda los valores actuales de los inputs de precio y costo.
     * @returns {{sellingPrices: object, rawHideCosts: object, buyingPrice: object}}
     */
    function getCurrentInputValues() {
        const currentSellingPrices = {};
        // Verifica si sellingPricesInputsDiv existe antes de usar querySelectorAll
        if (sellingPricesInputsDiv) {
            sellingPricesInputsDiv.querySelectorAll('input[type="number"]').forEach(input => {
                const match = input.name.match(/\[(t\d+)\]/);
                if (match && match[1]) {
                    currentSellingPrices[match[1]] = input.value;
                }
            });
        } else {
           console.error("Error: Element with ID 'sellingPricesInputs' not found!");
        }


        const currentRawHideCosts = {};
        // Verifica si rawHideCostsInputsDiv existe antes de usar querySelectorAll
        if (rawHideCostsInputsDiv) {
            rawHideCostsInputsDiv.querySelectorAll('input[type="number"]').forEach(input => {
                 const match = input.name.match(/\[(t\d+)\]/);
                 if (match && match[1]) {
                     currentRawHideCosts[match[1]] = input.value;
                 }
            });
        } else {
            console.error("Error: Element with ID 'rawHideCostsInputs' not found!");
        }


        const currentBuyingPrice = {};
        // Verifica si buyingPriceInputDiv existe antes de usar querySelectorAll
        if (buyingPriceInputDiv) {
            buyingPriceInputDiv.querySelectorAll('input[type="number"]').forEach(input => {
                 const match = input.name.match(/\[(t\d+)\]/);
                 if (match && match[1]) {
                      currentBuyingPrice[match[1]] = input.value;
                  }
            });
        } else {
            console.error("Error: Element with ID 'buyingPriceInput' not found!");
        }


        return {
            sellingPrices: currentSellingPrices,
            rawHideCosts: currentRawHideCosts,
            buyingPrice: currentBuyingPrice
        };
    }


    function populateTierSelects() {
        // Populate Starting Tier Select
        startingTierSelect.innerHTML = '';
        availableStartTiers.forEach(tier => {
            const option = document.createElement('option');
            option.value = tier;
            option.textContent = tier.toUpperCase();
            startingTierSelect.appendChild(option);
        });
        // Set default/initial value
        startingTierSelect.value = 't2'; // Or read from initial data if available

        // Populate Crafting Limit Tier Select (initially all tiers)
        craftingLimitTierSelect.innerHTML = '';
        availableLimitTiers.forEach(tier => {
            const option = document.createElement('option');
            option.value = tier;
            option.textContent = tier.toUpperCase();
            craftingLimitTierSelect.appendChild(option);
        });
        // Set default/initial value
        craftingLimitTierSelect.value = 't8'; // Or read from initial data if available
    }


    function updateDynamicInputs() {
        // 1. Guardar los valores actuales antes de borrar los inputs
        const currentValues = getCurrentInputValues();
        console.log("Valor de currentValues:", currentValues); // MENSAJE DE PRUEBA


        const startingTier = startingTierSelect.value;
        const craftingLimitTier = craftingLimitTierSelect.value;

        // 2. Actualizar opciones de Crafting Limit Tier
        const startIndex = allTiers.indexOf(startingTier);
        const limitIndex = allTiers.indexOf(craftingLimitTier);

        craftingLimitTierSelect.innerHTML = '';
        allTiers.slice(startIndex).forEach(tier => {
            const option = document.createElement('option');
            option.value = tier;
            option.textContent = tier.toUpperCase();
            craftingLimitTierSelect.appendChild(option);
        });
        if (startIndex > limitIndex) {
            craftingLimitTierSelect.value = startingTier;
        } else {
             craftingLimitTierSelect.value = craftingLimitTier;
        }

        // 3. Actualizar label y valor inicial de Cantidad Inicial de Materias Primas
        labelInitialHides.textContent = `Cantidad Inicial de Materias Primas (${startingTier.toUpperCase()}):`;
         if (startingTier === 't2') {
              labelInitialHides.textContent = `Cantidad Inicial de Materias Primas T2:`;
              if (initialHidesInput.value === '' || initialHidesInput.value === '0') {
                   initialHidesInput.value = '100';
              }
         } else {
              if (initialHidesInput.value === '100') {
                  initialHidesInput.value = '0';
              }
              labelInitialHides.textContent = `Cantidad Inicial de Materias Primas (${startingTier.toUpperCase()}):`;
         }


        // 4. Mostrar/Ocultar secci\u00f3n de Precio de Compra de Material Anterior
        const prevTierForStartingTier = getPrevTier(startingTier);
        if (startingTier !== 't2' && prevTierForStartingTier) {
            buyingPriceSectionDiv.style.display = 'block';
            buyingPriceInputDiv.innerHTML = `
                <div>
                    <label id="label_buying_price" for="buying_prices_${prevTierForStartingTier}" class="block text-sm font-medium text-gray-700">
                         Precio Compra Refinado (${prevTierForStartingTier.toUpperCase()}):
                    </label>
                    <input type="number" step="1" id="buying_prices_${prevTierForStartingTier}" name="buying_prices[${prevTierForStartingTier}]"
                         class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                         value="${currentValues.buyingPrice[prevTierForStartingTier] ?? defaultBuyingPrices[prevTierForStartingTier] ?? 0}" required min="0">
                </div>
            `;
        } else {
            buyingPriceSectionDiv.style.display = 'none';
             buyingPriceInputDiv.innerHTML = '';
        }


        // 5. Actualizar inputs de Precios de Venta y Costos de Materias Primas en Bruto
        const startIndexForInputs = allTiers.indexOf(startingTier);
        const limitIndexForInputs = allTiers.indexOf(craftingLimitTier);
        const displayedTiers = allTiers.slice(startIndexForInputs, limitIndexForInputs + 1);

        // Vaciamos los contenedores ANTES de a\u00f1adir los nuevos inputs
        sellingPricesInputsDiv.innerHTML = '';
        rawHideCostsInputsDiv.innerHTML = '';


        displayedTiers.forEach(tier => {
            // Selling Prices (Usamos currentValues.sellingPrices)
            const sellDiv = document.createElement('div');
            sellDiv.innerHTML = `
                <label for="selling_prices_${tier}" class="block text-sm font-medium text-gray-700">${tier.toUpperCase()} Venta:</label>
                <input type="number" step="1" id="selling_prices_${tier}" name="selling_prices[${tier}]"
                         class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                         value="${currentValues.sellingPrices[tier] ?? defaultSellingPrices[tier] ?? 0}" required min="0">
            `;
            sellingPricesInputsDiv.appendChild(sellDiv);

            // Raw Hide Costs (Usamos currentValues.rawHideCosts)
            const rawDiv = document.createElement('div');
            rawDiv.innerHTML = `
                <label for="raw_hide_costs_${tier}" class="block text-sm font-medium text-gray-700">${tier.toUpperCase()} Materias Primas:</label>
                <input type="number" step="1" id="raw_hide_costs_${tier}" name="raw_hide_costs[${tier}]"
                         class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2"
                         value="${currentValues.rawHideCosts[tier] ?? defaultRawHideCosts[tier] ?? 0}" required min="0">
            `;
            rawHideCostsInputsDiv.appendChild(rawDiv);
        });

    }


    /**
     * Muestra los errores de validaci\u00f3n en la interfaz.
     * @param {string[]} errors - Array de mensajes de error.
     */
    function displayErrors(errors) {
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
         }
    }

    /**
     * Muestra los resultados del c\u00e1lculo en la interfaz.
     * @param {object} results - Objeto con los resultados del backend.
     */
    function displayResults(results) {
         if (!results) return;

         // Mostrar Resumen General
         if (results.summary) {
             const summary = results.summary;
             let summaryHtml = `
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800">
                     <p><strong>Costo Total en Materias Primas Adquiridas:</strong> ${formatNumber(summary.total_raw_hide_cost)}</p>
                     ${(summary.total_acquired_leather_cost ?? 0) > 0 ? `<p><strong>Costo Total en Refinado Adquirido (Tier Anterior < Inicio):</strong> ${formatNumber(summary.total_acquired_leather_cost)}</p>` : ''}
                     <p><strong>Inversi\u00f3n Total en Materiales Adquiridos:</strong> ${formatNumber(summary.total_material_investment)}</p>
                      <p><strong>Costo Total de Alquiler:</strong> ${formatNumber(summary.total_rental_cost)}</p>
                     <p><strong>Ingresos Totales (Tier L\u00edmite):</strong> ${formatNumber(summary.total_revenue)}</p>
                     <p class="${summary.net_profit_loss >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/P\u00e9rdida Neta:</strong> ${formatNumber(summary.net_profit_loss)}</p>
                     <p class="${summary.net_profit_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/P\u00e9rdida Neta (%):</strong> ${formatNumber(summary.net_profit_loss_percentage, 2)} %</p>
                 </div>
             `;
             summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>' + summaryHtml;
         }

         // Mostrar Resultados por Tier
         let tierHtml = '';
          const startingTier = startingTierSelect.value;
          const craftingLimitTier = craftingLimitTierSelect.value;
          const startIndex = allTiers.indexOf(startingTier);
          const limitIndex = allTiers.indexOf(craftingLimitTier);
          const displayedResultTiers = allTiers.slice(startIndex, limitIndex + 1);

          displayedResultTiers.forEach(tier => {
              if (results[tier]) {
                  const tierData = results[tier];
                  const statusColor = tierData.status === 'profit' ? 'bg-green-100 border-green-400' :
                                       tierData.status === 'loss' ? 'bg-red-100 border-red-400' :
                                       'bg-gray-100 border-gray-400';

                  const profitLossColor = tierData.profit_loss_amount >= 0 ? 'text-green-700' : 'text-red-700';
                  const prevTier = getPrevTier(tier);

                  tierHtml += `
                      <div class="border ${statusColor} p-4 rounded-md shadow-sm mb-4">
                          <h3 class="text-lg font-medium mb-2">${tier.toUpperCase()} Refinado</h3>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-700">
                              <p><strong>Cantidad Producida:</strong> ${formatNumber(tierData.quantity, 0)}</p>
                              <p><strong>Materia Prima en Bruto Requeridas:</strong> ${formatNumber(tierData.required_raw_hides, 0)}</p>
                              ${(tierData.required_leather ?? 0) > 0 ? `<p><strong>Refinado Requerido (${prevTier ? prevTier.toUpperCase() : ''}):</strong> ${formatNumber(tierData.required_leather, 0)}</p>` : '<p><strong>Refinado Requerido:</strong> 0</p>'}
                               ${(tierData.rental_cost_per_unit ?? 0) > 0 ? `<p><strong>Costo por Unidad (Alquiler):</strong> ${formatNumber(tierData.rental_cost_per_unit)}</p>` : '<p><strong>Costo por Unidad (Alquiler):</strong> 0</p>'}
                              <p><strong>Costo por Unidad (Calculado):</strong> <span class="font-semibold">
                                          ${formatNumber(tierData.cost_per_unit ?? 0)}
                                      </span></p>
                              <p><strong>Precio de Venta Neto:</strong> <span class="font-semibold">${formatNumber(tierData.net_selling_price ?? 0)}</span></p>
                               ${(tierData.buying_price_prev_tier_material ?? 0) > 0 ? `<p><strong>Precio Compra Refinado Anterior (Adquirido):</strong> ${formatNumber(tierData.buying_price_prev_tier_material)}</p>` : ''}
                               <p class="${profitLossColor}"><strong>Ganancia/P\u00e9rdida (Cantidad):</strong> ${formatNumber(tierData.profit_loss_amount)}</p>
                               <p class="${profitLossColor}"><strong>Ganancia/P\u00e9rdida (%):</strong> ${formatNumber(tierData.profit_loss_percentage, 2)} %</p>
                              <p><strong>Estado:</strong>
                                   <span class="font-semibold">
                                       ${tierData.status === 'profit' ? '¡S\u00ed vender!' : tierData.status === 'loss' ? '¡No vender!' : 'No Crafteado'}
                                   </span>
                              </p>
                               ${tierData.status === 'profit' ? `<p>Ganancia: ${formatNumber(tierData.profit_loss_amount ?? 0)} (${formatNumber(tierData.profit_loss_percentage ?? 0, 2)}%)</p>` : ''}
                               ${tierData.status === 'loss' ? `<p>P\u00e9rdida: ${formatNumber(Math.abs(tierData.profit_loss_amount ?? 0))} (${formatNumber(Math.abs(tierData.profit_loss_percentage ?? 0), 2)}%)</p>` : ''}
                               ${tierData.status === 'not_crafted' ? `<p class="text-yellow-600">No se pudo producir suficiente material del tier anterior o no se seleccion\u00f3 este tier.</p>` : ''}
                          </div>
                      </div>
                  `;
              }
          });

          tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Tier</h2>' + tierHtml;
      }


    /**
     * Formatea un n\u00famero.
     * @param {number} number - El n\u00famero a formatear.
     * @param {number} [decimals=2] - N\u00famero de decimales.
     * @param {string} [locale='es-ES'] - Localizaci\u00f3n.
     * @returns {string} El n\u00famero formateado.
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


    /**
     * Recopila todos los valores de los inputs del formulario.
     * @returns {object} Un objeto con los valores de los inputs del formulario.
     */
     function collectAndParseFormData() {
        const parsedData = {};

        // Helper para parsear floats
        const getFloat = (elementId, defaultValue = 0) => {
            const el = document.getElementById(elementId);
            return el ? parseFloat(el.value) || defaultValue : defaultValue;
        };
        // Helper para parsear strings (selects)
        const getString = (elementId, defaultValue = '') => {
            const el = document.getElementById(elementId);
            return el ? el.value : defaultValue;
        };

        parsedData.rental_cost = getFloat('rental_cost');
        parsedData.purchase_percentage = getFloat('purchase_percentage');
        parsedData.sales_percentage = getFloat('sales_percentage');
        parsedData.tax_percentage = getFloat('tax_percentage');
        parsedData.return_percentage = getFloat('return_percentage');
        parsedData.starting_tier = getString('starting_tier');
        parsedData.crafting_limit_tier = getString('crafting_limit_tier');

        const initialHidesEl = document.querySelector('[name="initial_quantities[hides]"]');
        parsedData.initial_quantities = {
            hides: initialHidesEl ? parseFloat(initialHidesEl.value) || 0 : 0,
            leather: 0 // Siempre es 0 como input para esta calculadora
        };

        parsedData.buying_prices = {};
        allTiers.slice(0, -1).forEach(tier => { // T2 a T7
            const input = document.querySelector(`[name="buying_prices[${tier}]"]`);
            parsedData.buying_prices[tier] = input ? parseFloat(input.value) || 0 : 0;
        });

        parsedData.raw_hide_costs = {};
        allTiers.forEach(tier => { // T2 a T8
            const input = document.querySelector(`[name="raw_hide_costs[${tier}]"]`);
            parsedData.raw_hide_costs[tier] = input ? parseFloat(input.value) || 0 : 0;
        });

        parsedData.selling_prices = {};
        allTiers.forEach(tier => { // T2 a T8
            const input = document.querySelector(`[name="selling_prices[${tier}]"]`);
            parsedData.selling_prices[tier] = input ? parseFloat(input.value) || 0 : 0;
        });

        console.log("Collected and Parsed Form Data:", parsedData);
        return parsedData;
     }


    // --- Funciones de C\u00e1lculo Portadas de PHP a JS ---

    /**
     * Calcula la cantidad total de cuero producido dado una cantidad inicial de pieles en bruto
     * de ese tier y la receta.
     * (Portado de calculateLeatherFromRawHides en PHP)
     */
    function calculateLeatherFromRawHidesJS(initialRawHides, returnPercentage, reqRawPerCraft) {
        if (initialRawHides <= 0 || reqRawPerCraft <= 0) return 0;

        const retornoFraccion = returnPercentage / 100;
        let hidesAvailable = initialRawHides;
        let leatherProduced = 0;

        while (hidesAvailable >= reqRawPerCraft) {
            const craftableThisRound = Math.floor(hidesAvailable / reqRawPerCraft);
            if (craftableThisRound < 1) break;

            leatherProduced += craftableThisRound;
            const consumedHides = craftableThisRound * reqRawPerCraft;
            hidesAvailable -= consumedHides;

            const returnedHides = consumedHides * retornoFraccion;
            hidesAvailable += returnedHides;
        }
        return Math.floor(leatherProduced);
    }

    /**
     * Simula el refinamiento binario de un material de input (cuero de tier anterior)
     * para producir el \u00edtem del tier actual.
     * (Portado de simulateTierProductionFromPrevious en PHP)
     */
    function simulateTierProductionFromPreviousJS(availablePrevMaterial, reqPrevPerCraft, returnPercentage) {
        if (availablePrevMaterial <= 0 || reqPrevPerCraft <= 0) return 0;

        const returnFraction = returnPercentage / 100;
        let materialAvailable = availablePrevMaterial;
        let itemsProduced = 0;

        while (materialAvailable >= reqPrevPerCraft) {
            const craftableThisRound = Math.floor(materialAvailable / reqPrevPerCraft);
            if (craftableThisRound < 1) break;

            itemsProduced += craftableThisRound;
            const consumedMaterial = craftableThisRound * reqPrevPerCraft;
            materialAvailable -= consumedMaterial;

            const returnedMaterial = consumedMaterial * returnFraction;
            materialAvailable += returnedMaterial;
        }
        return Math.floor(itemsProduced);
    }

    /**
     * Calcula la cantidad m\u00ednima de un material de input necesaria para craftear una cantidad objetivo
     * de un \u00edtem que requiere ese material, considerando el retorno sobre el consumo de ese material.
     * (Portado de calculateRequiredInputWithReturn en PHP)
     */
    function calculateRequiredInputWithReturnJS(targetOutputQuantity, requiredPerCraft, returnPercentage) {
        if (targetOutputQuantity <= 0 || requiredPerCraft <= 0) return 0;

        const returnFraction = returnPercentage / 100;

        const checkCanProduce = (initial_input) => {
            let inputAvailable = initial_input;
            let outputProduced = 0;

            while (inputAvailable >= requiredPerCraft) {
                const canCraftThisRound = Math.floor(inputAvailable / requiredPerCraft);
                const craftThisRound = Math.min(canCraftThisRound, targetOutputQuantity - outputProduced);

                if (craftThisRound <= 0) break;

                outputProduced += craftThisRound;
                const consumedInput = craftThisRound * requiredPerCraft;
                inputAvailable -= consumedInput;
                const returnedInput = consumedInput * returnFraction;
                inputAvailable += returnedInput;

                if (outputProduced >= targetOutputQuantity) return true;
            }
            return outputProduced >= targetOutputQuantity;
        };

        // Ajustar los l\u00edmites de b\u00fasqueda para evitar bucles infinitos con inputs muy peque\u00f1os o retornos altos.
        // El l\u00edmite inferior debe ser al menos targetOutputQuantity si no hay retorno y se requiere 1 por crafteo.
        // El l\u00edmite superior puede ser targetOutputQuantity * requiredPerCraft como una estimaci\u00f3n inicial.
        let low = Math.ceil(targetOutputQuantity * requiredPerCraft * (1 - returnFraction)) -1; // Estimaci\u00f3n m\u00ednima te\u00f3rica
        if (low <0) low = 0;

        let high = targetOutputQuantity * requiredPerCraft * 2; // Estimaci\u00f3n inicial generosa del l\u00edmite superior

        // Asegurarse de que el l\u00edmite superior inicial sea suficiente
        let iterations = 0;
        const maxIterations = 20; // Prevenir bucles infinitos si algo va mal
        while (!checkCanProduce(high) && iterations < maxIterations) {
            high = high * 2;
            if (high > Number.MAX_SAFE_INTEGER / 2) {
                 console.error("High value too large in calculateRequiredInputWithReturnJS, breaking.");
                 return Math.ceil(targetOutputQuantity * requiredPerCraft); // Fallback: sin retorno
            }
            iterations++;
        }
        if (iterations >= maxIterations && !checkCanProduce(high)) {
            console.error("Could not find a high enough value in calculateRequiredInputWithReturnJS. Fallback.");
            return Math.ceil(targetOutputQuantity * requiredPerCraft); // Fallback
        }


        let minRequired = high; // Inicializar con un valor que sabemos que funciona

        // B\u00fasqueda binaria para encontrar la cantidad m\u00ednima requerida
        let searchLow = low; // Usar el 'low' calculado
        let searchHigh = high;
        iterations = 0; // Resetear contador para la b\u00fasqueda binaria

        while (searchLow <= searchHigh && iterations < 100) { // A\u00f1adir l\u00edmite de iteraciones
            const mid = Math.floor((searchLow + searchHigh) / 2);
            if (mid < 0) { // Asegurar que mid no sea negativo
                searchLow = 0; // Ajustar y continuar o romper si es necesario
                continue;
            }
            if (checkCanProduce(mid)) {
                minRequired = mid; // mid puede ser la respuesta, intenta con menos
                searchHigh = mid - 1;
            } else {
                searchLow = mid + 1; // mid no es suficiente, necesita m\u00e1s
            }
            iterations++;
        }
         if (iterations >= 100) {
            console.warn("Max iterations reached in binary search for calculateRequiredInputWithReturnJS. Result might not be optimal.");
        }

        return Math.ceil(minRequired); // Redondear hacia arriba ya que no puedes tener fracciones de items de input
    }
    // getPrevTier ya existe en el JS, se usar\u00e1 esa.


    // --- Funciones de Gesti\u00f3n de LocalStorage e Historial ---

    /**
     * Obtiene el historial de c\u00e1lculos de localStorage.
     * @returns {Array<object>} Un array de objetos de c\u00e1lculos guardados, o un array vac\u00edo si no hay.
     */
     function getCalculationHistory() {
        try {
            const historyJson = localStorage.getItem(LOCAL_STORAGE_KEY);
            return historyJson ? JSON.parse(historyJson) : [];
        } catch (e) {
            console.error("Error al leer de localStorage:", e);
            return []; // Retorna vac\u00edo si hay un error
        }
     }

    /**
     * Guarda un array de c\u00e1lculos en localStorage.
     * @param {Array<object>} history - El array de c\u00e1lculos a guardar.
     */
     function saveCalculationHistory(history) {
        try {
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(history));
            console.log("Historial guardado en localStorage:", history);
        } catch (e) {
            console.error("Error al escribir en localStorage:", e);
            alert("Error al guardar el historial.");
        }
     }

     /**
      * A\u00f1ade el c\u00e1lculo actual al historial y lo guarda en localStorage.
      * Limita el historial al n\u00famero definido por HISTORY_LIMIT.
      * Pide un nombre al usuario para este c\u00e1lculo.
      */
     function addCurrentCalculationToHistory() {
         const calculationName = prompt("Ingresa un nombre para este c\u00e1lculo:", `C\u00e1lculo ${new Date().toLocaleString()}`);

         // Si el usuario cancela o no ingresa nombre, no guardar
         if (!calculationName) {
             alert("Guardar c\u00e1lculo cancelado.");
             return;
         }

         const currentData = collectAndParseFormData(); // <--- CAMBIO AQU\u00cd
         // A\u00f1adir un ID \u00fanico y el nombre
         const calculationRecord = {
             id: Date.now() + Math.random().toString(16).slice(2), // ID simple basado en tiempo + aleatorio
             name: calculationName,
             data: currentData // Los datos del formulario
         };

         let history = getCalculationHistory();

         // A\u00f1adir el nuevo c\u00e1lculo al inicio del array (para que los m\u00e1s recientes aparezcan primero)
         history.unshift(calculationRecord);

         // Limitar el tama\u00f1o del historial
         if (history.length > HISTORY_LIMIT) {
             history = history.slice(0, HISTORY_LIMIT);
         }

         saveCalculationHistory(history);
         displayCalculationHistory(); // Actualizar la visualizaci\u00f3n del historial
         // alert("C\u00e1lculo '" + calculationName + "' guardado correctamente."); // Desactivado para evitar doble alert si se guarda al calcular
     }

     /**
      * Elimina un c\u00e1lculo espec\u00edfico del historial por su ID.
      * @param {string} id - El ID del c\u00e1lculo a eliminar.
      */
     function deleteCalculation(id) {
         let history = getCalculationHistory();
         const initialLength = history.length;

         // Filtrar el array, qued\u00e1ndose con todos los elementos cuyo ID no coincida con el ID a eliminar
         history = history.filter(calculation => calculation.id !== id);

         // Si el array cambi\u00f3 de tama\u00f1o, significa que se encontr\u00f3 y elimin\u00f3 el c\u00e1lculo
         if (history.length < initialLength) {
             saveCalculationHistory(history); // Guardar el historial actualizado
             displayCalculationHistory(); // Actualizar la visualizaci\u00f3n del historial
             alert("C\u00e1lculo eliminado correctamente.");
         } else {
             console.warn("Intent\u00f3 eliminar un c\u00e1lculo con ID no encontrado:", id);
         }
     }

     /**
      * Carga los datos de un c\u00e1lculo espec\u00edfico en el formulario por su ID.
      * @param {string} id - El ID del c\u00e1lculo a cargar.
      */
     function loadCalculation(id) {
         const history = getCalculationHistory();
         const calculationToLoad = history.find(calculation => calculation.id === id); // Buscar el c\u00e1lculo por ID

         if (calculationToLoad) {
             populateForm(calculationToLoad.data); // Rellenar el formulario con los datos guardados
             alert("C\u00e1lculo '" + calculationToLoad.name + "' cargado en el formulario.");
             // Opcional: Scroll hacia arriba al cargar para ver el formulario
             calculatorForm.scrollIntoView({ behavior: 'smooth' });
         } else {
             console.warn("Intent\u00f3 cargar un c\u00e1lculo con ID no encontrado:", id);
             alert("Error: C\u00e1lculo no encontrado en el historial.");
         }
     }

      /**
       * Rellena los campos del formulario con los datos de un calculo guardado.
       * @param {object} data - El objeto de datos del formulario guardado.
       */
       function populateForm(data) {
           if (!data) {
               console.error("No hay datos para rellenar el formulario.");
               return;
           }

           console.log("Iniciando populateForm con datos:", data);

           // 1. Rellenar inputs directos y selects (esto es rapido)
           ['rental_cost'].forEach(id => {
                const input = document.getElementById(id);
                if (input && data[id] !== undefined) input.value = data[id];
           });
            ['purchase_percentage', 'sales_percentage', 'tax_percentage', 'return_percentage', 'starting_tier', 'crafting_limit_tier'].forEach(id => {
                const select = document.getElementById(id);
                if (select && data[id] !== undefined) select.value = data[id];
            });

           // Rellenar initial_hides
            const initialHidesInput = document.querySelector('[name="initial_quantities[hides]"]');
            if (initialHidesInput && data['initial_hides'] !== undefined) {
                initialHidesInput.value = data['initial_hides'];
            }

           // 2. Asegurar que los selects de tier esten seteados para que updateDynamicInputs funcione bien
           const savedStartingTier = data.starting_tier ?? 't2';
           const savedCraftingLimitTier = data.crafting_limit_tier ?? 't8';

           startingTierSelect.value = savedStartingTier;
           craftingLimitTierSelect.value = savedCraftingLimitTier;

           // 3. **Llamar a updateDynamicInputs ahora mismo** para que borre y cree los campos correctos.
           // Esto es para asegurar que los campos que vamos a llenar existan en la pagina.
           updateDynamicInputs(); // <--- LLAMADA A updateDynamicInputs

           // 4. Usar un tiempo de espera pequeno para darle chance al navegador
           // de terminar de crear los campos antes de poner los valores.
           setTimeout(() => {
              console.log("setTimeout ejecutado en populateForm. Procediendo a rellenar inputs dinamicos.");

              // Ahora rellenar los campos de precio/costo con los datos guardados.
              // updateDynamicInputs ya los creo, ahora buscamos los nuevos campos por su nombre.
              if (data.raw_hide_costs) {
                  Object.keys(data.raw_hide_costs).forEach(tier => {
                      const input = document.querySelector(`[name="raw_hide_costs[${tier}]"]`);
                      if (input && data.raw_hide_costs[tier] !== undefined) {
                          input.value = data.raw_hide_costs[tier];
                      }
                  });
              }

                if (data.selling_prices) {
                   Object.keys(data.selling_prices).forEach(tier => {
                       const input = document.querySelector(`[name="selling_prices[${tier}]"]`);
                       if (input && data.selling_prices[tier] !== undefined) {
                           input.value = data.selling_prices[tier];
                       }
                   });
               }

                // Rellenar el campo de precio de compra si es visible para el tier cargado
                const prevTierForLoadedStartingTier = getPrevTier(savedStartingTier);
                if (prevTierForLoadedStartingTier) {
                     const input = document.querySelector(`[name="buying_prices[${prevTierForLoadedStartingTier}]"]`);
                     if (input && data.buying_prices && data.buying_prices[prevTierForLoadedStartingTier] !== undefined) {
                        input.value = data.buying_prices[prevTierForLoadedStartingTier];
                    }
                }

              // No necesitamos hacer nada mas aqui por ahora.
              // El usuario hara "click" en "Calcular" si quiere ver los resultados del calculo cargado.

           }, 50); // <--- Espera 50 milisegundos. Si aun necesitas mas de 1 click, puedes probar 100.

       }


    /**
     * Muestra el historial de c\u00e1lculos en la lista.
     */
     function displayCalculationHistory() {
         const history = getCalculationHistory();
         historyListUl.innerHTML = ''; // Limpiar la lista actual

         if (history.length === 0) {
             const li = document.createElement('li');
             li.textContent = 'No hay c\u00e1lculos guardados a\u00fan.';
             historyListUl.appendChild(li);
             // Ocultar la secci\u00f3n completa si no hay historial
             // calculationHistoryDiv.style.display = 'none';
         } else {
             // calculationHistoryDiv.style.display = 'block'; // Mostrar la secci\u00f3n si hay historial
             history.forEach(calculation => {
                 const li = document.createElement('li');
                 li.classList.add('history-item'); // A\u00f1adir clase para estilos
                 li.dataset.id = calculation.id; // A\u00f1adir el ID al li tambi\u00e9n, \u00fatil a veces

                 // Elemento clickeable para cargar (el nombre)
                 const nameSpan = document.createElement('span');
                 nameSpan.textContent = calculation.name;
                 nameSpan.title = "Haga clic para cargar este c\u00e1lculo"; // Tooltip
                 nameSpan.dataset.id = calculation.id; // Guardar el ID en el elemento para el listener

                 // Contenedor para los botones (para alinear)
                 const buttonsDiv = document.createElement('div');
                 buttonsDiv.classList.add('history-actions');

                 // Bot\u00f3n Eliminar
                 const deleteButton = document.createElement('button');
                 deleteButton.textContent = 'Eliminar';
                 deleteButton.classList.add('delete-btn'); // Clase para estilos
                 deleteButton.dataset.id = calculation.id; // Guardar el ID para el listener


                 // A\u00f1adir elementos a la lista
                 li.appendChild(nameSpan); // A\u00f1adir el nombre clickeable
                 buttonsDiv.appendChild(deleteButton); // A\u00f1adir el bot\u00f3n al contenedor
                 li.appendChild(buttonsDiv); // A\u00f1adir el contenedor de botones al item de la lista

                 historyListUl.appendChild(li);
             });

             // Despu\u00e9s de crear los elementos, adjuntar los listeners usando delegaci\u00f3n de eventos
             // para evitar a\u00f1adir un listener a cada span y bot\u00f3n individualmente.
             // Adjuntamos los listeners al contenedor principal de la lista (historyListUl)
             // y verificamos el target del evento.

             historyListUl.addEventListener('click', (event) => {
                 const target = event.target;
                 const calcId = target.dataset.id;

                 if (!calcId) return; // Salir si el elemento clickeado no tiene un data-id (no es un elemento del historial)

                 if (target.tagName === 'SPAN') { // Clic en el nombre (elemento span)
                      loadCalculation(calcId);
                 } else if (target.tagName === 'BUTTON' && target.classList.contains('delete-btn')) { // Clic en el bot\u00f3n Eliminar
                     // Encontrar el nombre del c\u00e1lculo para la confirmaci\u00f3n
                     const history = getCalculationHistory();
                     const calculationToDelete = history.find(calc => calc.id === calcId);
                      const calculationName = calculationToDelete ? calculationToDelete.name : "este calculo";

                     if (confirm(`Deseas eliminarlo "${calculationName}"?`)) {
                         deleteCalculation(calcId);
                     }
                 }
             });

         }
     }


    // --- Funciones de Visualizaci\u00f3n de Resultados y Errores ---
    // (Sin cambios relevantes, pero incluidas para completitud)

    function displayErrors(errors) {
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
         }
    }

    function displayResults(results) {
         if (!results) return;

         // Mostrar Resumen General
         if (results.summary) {
             const summary = results.summary;
             let summaryHtml = `
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-800">
                     <p><strong>Costo Total en Materias Primas Adquiridas:</strong> ${formatNumber(summary.total_raw_hide_cost)}</p>
                     ${(summary.total_acquired_leather_cost ?? 0) > 0 ? `<p><strong>Costo Total en Refinado Adquirido (Tier Anterior < Inicio):</strong> ${formatNumber(summary.total_acquired_leather_cost)}</p>` : ''}
                     <p><strong>Inversi\u00f3n Total en Materiales Adquiridos:</strong> ${formatNumber(summary.total_material_investment)}</p>
                      <p><strong>Costo Total de Alquiler:</strong> ${formatNumber(summary.total_rental_cost)}</p>
                     <p><strong>Ingresos Totales (Tier Límite):</strong> ${formatNumber(summary.total_revenue)}</p>
                     <p class="${summary.net_profit_loss >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/P\u00e9rdida Neta:</strong> ${formatNumber(summary.net_profit_loss)}</p>
                     <p class="${summary.net_profit_loss_percentage >= 0 ? 'text-green-600' : 'text-red-600'}"><strong>Ganancia/P\u00e9rdida Neta (%):</strong> ${formatNumber(summary.net_profit_loss_percentage, 2)} %</p>
                 </div>
             `;
             summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>' + summaryHtml;
         }

         // Mostrar Resultados por Tier
         let tierHtml = '';
          const startingTier = startingTierSelect.value;
          const craftingLimitTier = craftingLimitTierSelect.value;
          const startIndex = allTiers.indexOf(startingTier);
          const limitIndex = allTiers.indexOf(craftingLimitTier);
          const displayedResultTiers = allTiers.slice(startIndex, limitIndex + 1);

          displayedResultTiers.forEach(tier => {
              if (results[tier]) {
                  const tierData = results[tier];
                  const statusColor = tierData.status === 'profit' ? 'bg-green-100 border-green-400' :
                                       tierData.status === 'loss' ? 'bg-red-100 border-red-400' :
                                       'bg-gray-100 border-gray-400';

                  const profitLossColor = tierData.profit_loss_amount >= 0 ? 'text-green-700' : 'text-red-700';
                  const prevTier = getPrevTier(tier);

                  tierHtml += `
                      <div class="border ${statusColor} p-4 rounded-md shadow-sm mb-4">
                          <h3 class="text-lg font-medium mb-2">${tier.toUpperCase()} Refinado</h3>
                          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-700">
                              <p><strong>Cantidad Producida:</strong> ${formatNumber(tierData.quantity, 0)}</p>
                              <p><strong>Materias Primas en Bruto Requeridas:</strong> ${formatNumber(tierData.required_raw_hides, 0)}</p>
                              ${(tierData.required_leather ?? 0) > 0 ? `<p><strong>Refinado Requerido (${prevTier ? prevTier.toUpperCase() : ''}):</strong> ${formatNumber(tierData.required_leather, 0)}</p>` : '<p><strong>Refinado Requerido:</strong> 0</p>'}
                               ${(tierData.rental_cost_per_unit ?? 0) > 0 ? `<p><strong>Costo por Unidad (Alquiler):</strong> ${formatNumber(tierData.rental_cost_per_unit)}</p>` : '<p><strong>Costo por Unidad (Alquiler):</strong> 0</p>'}
                              <p><strong>Costo por Unidad (Calculado):</strong> <span class="font-semibold">
                                          ${formatNumber(tierData.cost_per_unit ?? 0)}
                                      </span></p>
                              <p><strong>Precio de Venta Neto:</strong> <span class="font-semibold">${formatNumber(tierData.net_selling_price ?? 0)}</span></p>
                               ${(tierData.buying_price_prev_tier_material ?? 0) > 0 ? `<p><strong>Precio Compra Refinado Anterior (Adquirido):</strong> ${formatNumber(tierData.buying_price_prev_tier_material)}</p>` : ''}
                               <p class="${profitLossColor}"><strong>Ganancia/P\u00e9rdida (Cantidad):</strong> ${formatNumber(tierData.profit_loss_amount)}</p>
                               <p class="${profitLossColor}"><strong>Ganancia/P\u00e9rdida (%):</strong> ${formatNumber(tierData.profit_loss_percentage, 2)} %</p>
                              <p><strong>Estado:</strong>
                                   <span class="font-semibold">
                                       ${tierData.status === 'profit' ? '¡S\u00ed vender!' : tierData.status === 'loss' ? '¡No vender!' : 'No Crafteado'}
                                   </span>
                              </p>
                               ${tierData.status === 'profit' ? `<p>Ganancia: ${formatNumber(tierData.profit_loss_amount ?? 0)} (${formatNumber(tierData.profit_loss_percentage ?? 0, 2)}%)</p>` : ''}
                               ${tierData.status === 'loss' ? `<p>P\u00e9rdida: ${formatNumber(Math.abs(tierData.profit_loss_amount ?? 0))} (${formatNumber(Math.abs(tierData.profit_loss_percentage ?? 0), 2)}%)</p>` : ''}
                               ${tierData.status === 'not_crafted' ? `<p class="text-yellow-600">No se pudo producir suficiente material del tier anterior o no se seleccion\u00f3 este tier.</p>` : ''}
                          </div>
                      </div>
                  `;
              }
          });

          tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Tier</h2>' + tierHtml;
      }


    /**
     * Formatea un n\u00famero.
     * @param {number} number - El n\u00famero a formatear.
     * @param {number} [decimals=2] - N\u00famero de decimales.
     * @param {string} [locale='es-ES'] - Localizaci\u00f3n.
     * @returns {string} El n\u00famero formateado.
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


    // --- Inicializar ---
    // Asegurarse de que los elementos HTML existan antes de intentar usarlos
    if (startingTierSelect && craftingLimitTierSelect && initialHidesInput && labelInitialHides &&
        buyingPriceSectionDiv && buyingPriceInputDiv && sellingPricesInputsDiv && rawHideCostsInputsDiv &&
        calculatorForm && validationErrorsDiv && summaryResultsDiv && tierResultsDiv && saveCalculationButton &&
        calculationHistoryDiv && historyListUl) { // <-- Incluimos los nuevos elementos del historial

        // 1. Rellenar los selects de tier al cargar la p\u00e1gina
        populateTierSelects();

        // 2. Cargar el historial de localStorage y mostrarlo
        displayCalculationHistory();

        // 3. Generar los inputs din\u00e1micos iniciales (usar\u00e1 getCurrentInputValues que estar\u00e1 vac\u00edo al inicio)
        updateDynamicInputs();

        // 4. Limpiar \u00e1reas de resultados y errores al cargar la p\u00e1gina
        validationErrorsDiv.innerHTML = '';
        summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>';
        tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Tier</h2>';


        // --- Event Listeners ---
        startingTierSelect.addEventListener('change', updateDynamicInputs);
        craftingLimitTierSelect.addEventListener('change', updateDynamicInputs);

        // Listener para el bot\u00f3n "Guardar C\u00e1lculo"
        saveCalculationButton.addEventListener('click', addCurrentCalculationToHistory); // <-- Llama a la nueva funci\u00f3n addCurrentCalculationToHistory


        // Listener para el env\u00edo del formulario
        calculatorForm.addEventListener('submit', (event) => {
            event.preventDefault();
            console.log("Submit button clicked! Performing calculations in JS (Leather Calculator).");

            // Limpiar resultados anteriores y errores
            validationErrorsDiv.innerHTML = '';
            validationErrorsDiv.classList.remove('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4');
            summaryResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resumen General</h2>';
            tierResultsDiv.innerHTML = '<h2 class="text-xl font-bold mb-4">Resultados por Tier</h2>';

            const parsedFormData = collectAndParseFormData();
            const validationErrors = validateLeatherCalculatorInput(parsedFormData);

            if (validationErrors.length > 0) {
                displayErrors(validationErrors);
                return;
            }

            const results = performLeatherCalculations(parsedFormData);
            displayResults(results);
        });


    function validateLeatherCalculatorInput(formData) {
        const errors = [];
        const numericFields = ['rental_cost', 'purchase_percentage', 'sales_percentage', 'tax_percentage', 'return_percentage'];
        numericFields.forEach(field => {
            if (isNaN(formData[field]) || formData[field] < 0) {
                errors.push(`El campo ${field} es requerido y debe ser un n\u00famero positivo.`);
            }
        });
        if (isNaN(formData.initial_quantities.hides) || formData.initial_quantities.hides < 0) {
             errors.push('El campo Cantidad Inicial de Pieles es requerido y debe ser un n\u00famero positivo.');
        }

        const validStartingTiers = allTiers.slice(0, -1); // T2 a T7
        const validCraftingTiers = allTiers; // T2 a T8

        if (!formData.starting_tier || !validStartingTiers.includes(formData.starting_tier)) {
            errors.push('El tier de inicio seleccionado es inv\u00e1lido.');
        }
        if (!formData.crafting_limit_tier || !validCraftingTiers.includes(formData.crafting_limit_tier)) {
            errors.push('El tier l\u00edmite de crafteo seleccionado es inv\u00e1lido.');
        }

        const startIndexVal = allTiers.indexOf(formData.starting_tier);
        const limitIndexVal = allTiers.indexOf(formData.crafting_limit_tier);

        if (startIndexVal === -1 || limitIndexVal === -1) {
            if (!errors.some(e => e.includes("tier de inicio") || e.includes("tier l\u00edmite"))) { // Evitar error duplicado si ya existe uno sobre el tier
                 errors.push('Uno de los tiers seleccionados no es v\u00e1lido para la comparaci\u00f3n de \u00edndices.');
            }
        } else if (startIndexVal > limitIndexVal) {
            errors.push('El tier l\u00edmite de crafteo no puede ser menor que el tier de inicio.');
        }

        const validReturnPercentages = [36.7, 43.5, 53.9];
        if (!validReturnPercentages.includes(formData.return_percentage)) {
             errors.push('El porcentaje de retorno seleccionado es inv\u00e1lido.');
        }

        if (formData.crafting_limit_tier && formData.selling_prices[formData.crafting_limit_tier] !== undefined) {
            if (isNaN(formData.selling_prices[formData.crafting_limit_tier]) || formData.selling_prices[formData.crafting_limit_tier] < 0) {
                errors.push(`El valor para el precio de venta en el tier ${formData.crafting_limit_tier} es inv\u00e1lido o falta.`);
            }
        } else if (formData.crafting_limit_tier && validCraftingTiers.includes(formData.crafting_limit_tier)) { // Solo si el tier l\u00edmite es v\u00e1lido
            errors.push(`Falta el precio de venta para el tier l\u00edmite ${formData.crafting_limit_tier}.`);
        }


        if (startIndexVal !== -1 && limitIndexVal !== -1 && startIndexVal <= limitIndexVal) {
            for (let i = startIndexVal; i <= limitIndexVal; i++) {
                 const currentTierVal = allTiers[i];
                 if (formData.raw_hide_costs[currentTierVal] === undefined || isNaN(formData.raw_hide_costs[currentTierVal]) || formData.raw_hide_costs[currentTierVal] < 0) {
                     errors.push(`El valor para los costos de pieles en bruto en el tier ${currentTierVal} es inv\u00e1lido o falta.`);
                 }
            }
        }

        if (formData.starting_tier && formData.starting_tier !== 't2' && validStartingTiers.includes(formData.starting_tier)) { // Solo si el tier de inicio es v\u00e1lido
            const prevTierForStarting = getPrevTier(formData.starting_tier);
            if (prevTierForStarting) {
                if (formData.buying_prices[prevTierForStarting] === undefined || isNaN(formData.buying_prices[prevTierForStarting]) || formData.buying_prices[prevTierForStarting] < 0) {
                    errors.push(`El valor para el precio de compra en el tier ${prevTierForStarting} es inv\u00e1lido o falta.`);
                }
            }
        }
        return errors;
    }

    function performLeatherCalculations(formData) {
        const {
            starting_tier, crafting_limit_tier, rental_cost, purchase_percentage,
            sales_percentage, tax_percentage, return_percentage, initial_quantities,
            selling_prices, raw_hide_costs, buying_prices
        } = formData;

        const purchaseMultiplier = 1 + (purchase_percentage / 100);
        const totalSaleTaxPercentage = sales_percentage + tax_percentage;
        const netSellingPriceMultiplier = 1 - (totalSaleTaxPercentage / 100);

        const craftedQuantity = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const requiredRawHides = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const requiredLeather = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const totalCostPerTier = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const costPerUnit = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const netSellingPricePerTier = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const profitLossAmountPerTier = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const profitLossPercentagePerTier = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const tierStatus = Object.fromEntries(allTiers.map(tier => [tier, 'not_crafted']));
        const totalRentalCostPerTier = Object.fromEntries(allTiers.map(tier => [tier, 0]));
        const rentalCostPerUnit = Object.fromEntries(allTiers.map(tier => [tier, 0]));

        const startIndex = allTiers.indexOf(starting_tier);
        const limitIndex = allTiers.indexOf(crafting_limit_tier);
        let availableLeatherFromPreviousTier = 0;

        for (let i = startIndex; i <= limitIndex; i++) {
            const currentTier = allTiers[i];
            const recipe = recipesJS[currentTier];
            if (!recipe) {
                console.error(`No se encontr\u00f3 receta para el tier: ${currentTier}`);
                tierStatus[currentTier] = 'not_crafted';
                continue;
            }
            const prevTier = recipe.prev_tier;
            const reqPrev = recipe.req_prev;
            const reqRaw = recipe.req_raw;
            const rawCost = (raw_hide_costs[currentTier] || 0) * purchaseMultiplier;

            netSellingPricePerTier[currentTier] = (selling_prices[currentTier] || 0) * netSellingPriceMultiplier;
            let costOfInputMaterialsForTier = 0;

            const startingTierIndex = startIndex;
            const prevTierIndex = (prevTier !== null) ? allTiers.indexOf(prevTier) : -1;

            if (currentTier === starting_tier) {
                const initialRawHidesForStartingTier = initial_quantities.hides;
                const producedStartingLeather = calculateLeatherFromRawHidesJS(initialRawHidesForStartingTier, return_percentage, reqRaw);
                craftedQuantity[currentTier] = Math.floor(producedStartingLeather);

                if (craftedQuantity[currentTier] <= 0) {
                    availableLeatherFromPreviousTier = 0;
                    tierStatus[currentTier] = 'not_crafted';
                    continue;
                }
                requiredRawHides[currentTier] = initialRawHidesForStartingTier;

                if (startingTierIndex > 0 && prevTier && reqPrev > 0) {
                    requiredLeather[currentTier] = calculateRequiredInputWithReturnJS(craftedQuantity[currentTier], reqPrev, return_percentage);
                } else {
                    requiredLeather[currentTier] = 0;
                }

                const costOfConsumedRawHides = initialRawHidesForStartingTier * rawCost;
                let costOfConsumedLeather = 0;
                if (startingTierIndex > 0 && prevTier && reqPrev > 0) {
                     costOfConsumedLeather = (requiredLeather[currentTier] || 0) * (buying_prices[prevTier] || 0);
                }
                costOfInputMaterialsForTier = costOfConsumedRawHides + costOfConsumedLeather;
                availableLeatherFromPreviousTier = craftedQuantity[currentTier];
            } else {
                const availablePrevTierLeatherForCrafting = availableLeatherFromPreviousTier;
                if (availablePrevTierLeatherForCrafting < reqPrev && reqPrev > 0) {
                    availableLeatherFromPreviousTier = 0;
                    tierStatus[currentTier] = 'not_crafted';
                    continue;
                }

                craftedQuantity[currentTier] = simulateTierProductionFromPreviousJS(availablePrevTierLeatherForCrafting, reqPrev, return_percentage);

                if (craftedQuantity[currentTier] <= 0) {
                    availableLeatherFromPreviousTier = 0;
                    tierStatus[currentTier] = 'not_crafted';
                    continue;
                }

                requiredRawHides[currentTier] = calculateRequiredInputWithReturnJS(craftedQuantity[currentTier], reqRaw, return_percentage);
                if (reqPrev > 0) {
                    requiredLeather[currentTier] = calculateRequiredInputWithReturnJS(craftedQuantity[currentTier], reqPrev, return_percentage);
                } else {
                    requiredLeather[currentTier] = 0;
                }

                let costOfConsumedLeather = 0;
                if (prevTierIndex !== -1 && prevTierIndex < startingTierIndex && reqPrev > 0) {
                    costOfConsumedLeather = (requiredLeather[currentTier] || 0) * (buying_prices[prevTier] || 0);
                } else if (prevTier && reqPrev > 0) {
                    costOfConsumedLeather = (requiredLeather[currentTier] || 0) * (costPerUnit[prevTier] || 0);
                }
                const costOfConsumedRawHides = (requiredRawHides[currentTier] * rawCost);
                costOfInputMaterialsForTier = costOfConsumedLeather + costOfConsumedRawHides;
                availableLeatherFromPreviousTier = craftedQuantity[currentTier];
            }

            const buildingValue = buildingValuesJS[currentTier] || 0;
            const rentalCostForTier = (craftedQuantity[currentTier] > 0) ? (rental_cost / 100) * buildingValue * 0.1125 * craftedQuantity[currentTier] : 0;
            totalRentalCostPerTier[currentTier] = rentalCostForTier;
            rentalCostPerUnit[currentTier] = (craftedQuantity[currentTier] > 0) ? rentalCostForTier / craftedQuantity[currentTier] : 0;

            totalCostPerTier[currentTier] = costOfInputMaterialsForTier + totalRentalCostPerTier[currentTier];
            costPerUnit[currentTier] = (craftedQuantity[currentTier] > 0) ? totalCostPerTier[currentTier] / craftedQuantity[currentTier] : 0;

            profitLossAmountPerTier[currentTier] = (netSellingPricePerTier[currentTier] - (costPerUnit[currentTier] || 0)) * craftedQuantity[currentTier];
            const investmentForPercentage = (totalCostPerTier[currentTier] || 0);

            if (investmentForPercentage === 0) {
                profitLossPercentagePerTier[currentTier] = profitLossAmountPerTier[currentTier] > 0 ? Infinity : 0;
            } else {
                profitLossPercentagePerTier[currentTier] = (profitLossAmountPerTier[currentTier] / investmentForPercentage) * 100;
            }

            if (craftedQuantity[currentTier] > 0) {
                tierStatus[currentTier] = ((netSellingPricePerTier[currentTier] || 0) >= (costPerUnit[currentTier] || 0)) ? 'profit' : 'loss';
            } else {
                tierStatus[currentTier] = 'not_crafted';
            }
        }

        const resultsForDisplay = {};
        let totalRawHideCostSummary = 0;
        let totalAcquiredLeatherCostSummary = 0;
        let totalRentalCostSummary = 0;

        const resultTiers = allTiers.slice(startIndex, limitIndex + 1);

        resultTiers.forEach(tier => {
            const prevTier = getPrevTier(tier);
            const startingTierIdx = allTiers.indexOf(starting_tier);
            const prevTierIdx = prevTier ? allTiers.indexOf(prevTier) : -1;
            let buyingPriceForDisplay = 0;
            if (prevTierIdx !== -1 && prevTierIdx < startingTierIdx && buying_prices[prevTier] !== undefined) {
                 buyingPriceForDisplay = buying_prices[prevTier] || 0;
            }

            resultsForDisplay[tier] = {
                quantity: craftedQuantity[tier],
                required_raw_hides: Math.round(requiredRawHides[tier]),
                required_leather: Math.round(requiredLeather[tier]),
                cost_per_unit: parseFloat(costPerUnit[tier].toFixed(2)),
                rental_cost_per_unit: parseFloat(rentalCostPerUnit[tier].toFixed(2)),
                selling_price: selling_prices[tier] || 0,
                net_selling_price: parseFloat(netSellingPricePerTier[tier].toFixed(2)),
                buying_price_prev_tier_material: parseFloat(buyingPriceForDisplay.toFixed(2)),
                profit_loss_amount: parseFloat(profitLossAmountPerTier[tier].toFixed(2)),
                profit_loss_percentage: parseFloat(profitLossPercentagePerTier[tier].toFixed(2)),
                status: tierStatus[tier],
            };
            if ((craftedQuantity[tier] || 0) > 0) {
                totalRentalCostSummary += (totalRentalCostPerTier[tier] || 0);
            }
        });

        totalRawHideCostSummary += (initial_quantities.hides || 0) * ((raw_hide_costs[starting_tier] || 0) * purchaseMultiplier);
        for (let i = startIndex + 1; i <= limitIndex; i++) {
            const currentTier = allTiers[i];
            if ((craftedQuantity[currentTier] || 0) > 0) {
                totalRawHideCostSummary += (requiredRawHides[currentTier] || 0) * ((raw_hide_costs[currentTier] || 0) * purchaseMultiplier);
            }
        }

        for (let i = startIndex; i <= limitIndex; i++) {
            const currentTier = allTiers[i];
            const recipe = recipesJS[currentTier];
            if (!recipe) continue;
            const prevTier = recipe.prev_tier;
            const prevTierIdx = prevTier ? allTiers.indexOf(prevTier) : -1;
            const startingTierIdx = allTiers.indexOf(starting_tier);

            if ((craftedQuantity[currentTier] || 0) > 0 && prevTierIdx !== -1 && prevTierIdx < startingTierIdx && buying_prices[prevTier] !== undefined) {
                totalAcquiredLeatherCostSummary += (requiredLeather[currentTier] || 0) * (buying_prices[prevTier] || 0);
            }
        }

        const totalMaterialInvestmentSummary = totalRawHideCostSummary + totalAcquiredLeatherCostSummary;
        const finalTier = crafting_limit_tier;
        const totalRevenue = (resultsForDisplay[finalTier]?.quantity || 0) * (resultsForDisplay[finalTier]?.net_selling_price || 0);
        const netProfitLossSummary = totalRevenue - totalMaterialInvestmentSummary - totalRentalCostSummary;
        const totalInvestmentForPercentageOverall = totalMaterialInvestmentSummary + totalRentalCostSummary;

        let netProfitLossPercentageSummary = 0;
        if (totalInvestmentForPercentageOverall > 0) {
            netProfitLossPercentageSummary = (netProfitLossSummary / totalInvestmentForPercentageOverall) * 100;
        } else if (netProfitLossSummary > 0) {
            netProfitLossPercentageSummary = Infinity;
        }

        resultsForDisplay.summary = {
            total_raw_hide_cost: parseFloat(totalRawHideCostSummary.toFixed(2)),
            total_acquired_leather_cost: parseFloat(totalAcquiredLeatherCostSummary.toFixed(2)),
            total_material_investment: parseFloat(totalMaterialInvestmentSummary.toFixed(2)),
            total_rental_cost: parseFloat(totalRentalCostSummary.toFixed(2)),
            total_revenue: parseFloat(totalRevenue.toFixed(2)),
            net_profit_loss: parseFloat(netProfitLossSummary.toFixed(2)),
            net_profit_loss_percentage: parseFloat(netProfitLossPercentageSummary.toFixed(2)),
        };

        return resultsForDisplay;
    }
        // Delegaci\u00f3n de eventos para los items del historial (cargar y eliminar) - SIN CAMBIOS
        // Adjuntamos el listener al contenedor principal de la lista (historyListUl)
        historyListUl.addEventListener('click', (event) => {
            const target = event.target;
            const calcId = target.dataset.id; // Obtenemos el ID del elemento clickeado o su ancestro con data-id

            // Si el clic no fue en un elemento con data-id (como el propio ul), salimos
            if (!calcId) {
                 // Si el clic fue en el span que contiene el nombre, el data-id estar\u00e1 en el span
                 const parentLi = target.closest('li');
                 if (parentLi && parentLi.dataset.id) {
                     const parentCalcId = parentLi.dataset.id;
                     if (target.tagName === 'SPAN') { // Clic en el nombre (elemento span)
                         loadCalculation(parentCalcId);
                     } else if (target.tagName === 'BUTTON' && target.classList.contains('delete-btn')) { // Clic en el bot\u00f3n Eliminar
                         const history = getCalculationHistory();
                         const calculationToDelete = history.find(calc => calc.id === parentCalcId);
                         const calculationName = calculationToDelete ? calculationToDelete.name : "este c\u00e1lculo";
                          if (confirm(`\u00bfEst\u00e1s seguro de que quieres eliminar "${calculationName}"?`)) {
                              deleteCalculation(parentCalcId);
                          }
                      }
                 }
                 return;
            }


            if (target.tagName === 'SPAN') { // Clic directo en el span con data-id
                 loadCalculation(calcId);
            } else if (target.tagName === 'BUTTON' && target.classList.contains('delete-btn')) { // Clic directo en el bot\u00f3n con data-id
                const history = getCalculationHistory();
                const calculationToDelete = history.find(calc => calc.id === calcId);
                 const calculationName = calculationToDelete ? calculationToDelete.name : "este c\u00e1lculo";
                if (confirm(`\u00bfEst\u00e1s seguro de que quieres eliminar "${calculationName}"?`)) {
                    deleteCalculation(calcId);
                }
            }
        });


    } else {
        console.error("Error: Uno o m\u00e1s elementos del formulario o historial no fueron encontrados al cargar la p\u00e1gina.");
         // Mostrar un mensaje de error visible al usuario si elementos cruciales faltan
         const errorMessageDiv = document.createElement('div');
         errorMessageDiv.classList.add('p-4', 'border', 'border-red-400', 'bg-red-100', 'rounded-md', 'mb-4', 'text-red-700');
         errorMessageDiv.textContent = "Error cr\u00edtico: No se pudieron encontrar todos los elementos necesarios en la p\u00e1gina para inicializar la calculadora. Verifica el HTML y los IDs.";
         // Intentar a\u00f1adirlo al body o a alguna parte accesible
         document.body.prepend(errorMessageDiv);
    }

});