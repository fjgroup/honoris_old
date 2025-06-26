<?php

header('Content-Type: application/json'); // Indicar que la respuesta es JSON

// Permitir peticiones desde cualquier origen (para desarrollo)
// En producción, deberías restringir esto a tus dominios
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');


// Definición de las recetas (ingredientes necesarios por tier)
$recipes = [
    't3' => ['prev_tier' => 't2', 'req_prev' => 1, 'req_raw' => 2],
    't4' => ['prev_tier' => 't3', 'req_prev' => 1, 'req_raw' => 2],
    't5' => ['prev_tier' => 't4', 'req_prev' => 1, 'req_raw' => 3],
    't6' => ['prev_tier' => 't5', 'req_prev' => 1, 'req_raw' => 4],
    't7' => ['prev_tier' => 't6', 'req_prev' => 1, 'req_raw' => 5],
    't8' => ['prev_tier' => 't7', 'req_prev' => 1, 'req_raw' => 5],
    't2' => ['prev_tier' => null, 'req_prev' => 0, 'req_raw' => 1], // T2 solo requiere piel cruda T2
];

// Valores de edificio por tier para el cálculo del alquiler
$buildingValues = [
    't2' => 0,
    't3' => 8,
    't4' => 16,
    't5' => 32,
    't6' => 64,
    't7' => 128,
    't8' => 256,
];

// Todos los tiers posibles
$allTiers = ['t2', 't3', 't4', 't5', 't6', 't7', 't8'];


// --- Funciones de Cálculo (Adaptadas del Controlador) ---

/**
 * Calcula la cantidad total de cuero producido dado una cantidad inicial de pieles en bruto
 * de ese tier y la receta.
 * Simula el proceso de refinamiento binario para pieles en bruto -> cuero para un tier específico.
 *
 * @param float $initialRawHides Cantidad inicial de pieles en bruto de ESTE tier.
 * @param float $returnPercentage Porcentaje de retorno.
 * @param int $reqRawPerCraft Cantidad de pieles en bruto de ESTE tier necesarias por crafteo de cuero de ESTE tier.
 * @return float La cantidad total de cuero de ESTE tier producido.
 */
function calculateLeatherFromRawHides($initialRawHides, $returnPercentage, $reqRawPerCraft)
{
    if ($initialRawHides <= 0 || $reqRawPerCraft <= 0) return 0;

    $retornoFraccion = $returnPercentage / 100;
    $hidesAvailable = $initialRawHides;
    $leatherProduced = 0;

    while ($hidesAvailable >= $reqRawPerCraft) {
        $craftableThisRound = floor($hidesAvailable / $reqRawPerCraft);
        if ($craftableThisRound < 1) break;

        $leatherProduced += $craftableThisRound;
        $consumedHides = $craftableThisRound * $reqRawPerCraft;
        $hidesAvailable -= $consumedHides;

        $return = $consumedHides * $retornoFraccion;
        $hidesAvailable += $return;
    }

    return floor($leatherProduced);
}

/**
 * Simula el refinamiento binario de un material de input (cuero de tier anterior)
 * para producir el ítem del tier actual.
 * La cantidad de crafteos se limita por la cantidad del material de tier anterior disponible,
 * aplicando retorno a ese material consumido.
 * El requisito de pieles en bruto no limita la cantidad de crafteos en esta simulación,
 * pero sí se considera para el costo total.
 *
 * @param float $availablePrevMaterial Cantidad disponible del material del tier anterior.
 * @param int $reqPrevPerCraft Cantidad del material anterior requerida por cada crafteo (según receta).
 * @param float $returnPercentage El porcentaje de retorno.
 * @return float La cantidad total producida del ítem del tier actual.
 */
function simulateTierProductionFromPrevious($availablePrevMaterial, $reqPrevPerCraft, $returnPercentage)
{
    if ($availablePrevMaterial <= 0 || $reqPrevPerCraft <= 0) return 0;

    $returnFraction = $returnPercentage / 100;
    $materialAvailable = $availablePrevMaterial;
    $itemsProduced = 0;

    // Simulación del proceso de crafteo con retorno en el material del tier anterior
    while ($materialAvailable >= $reqPrevPerCraft) {
        $craftableThisRound = floor($materialAvailable / $reqPrevPerCraft);
        if ($craftableThisRound < 1) break;

        $itemsProduced += $craftableThisRound;
        $consumedMaterial = $craftableThisRound * $reqPrevPerCraft;
        $materialAvailable -= $consumedMaterial;

        $return = $consumedMaterial * $returnFraction;
        $materialAvailable += $return;
    }

    return floor($itemsProduced);
}

/**
 * Calcula la cantidad mínima de un material de input necesaria para craftear una cantidad objetivo
 * de un ítem que requiere ese material, considerando el retorno sobre el consumo de ese material.
 * Simula la lógica de búsqueda binaria para determinar el input mínimo.
 * Usado para calcular las pieles en bruto requeridas y el cuero anterior requerido basado en la cantidad *producida*.
 *
 * @param int $targetOutputQuantity La cantidad del ítem final que se desea producir (output).
 * @param int $requiredPerCraft La cantidad del material de input requerida por cada crafteo base.
 * @param float $returnPercentage El porcentaje de retorno.
 * @return int La cantidad mínima total del material de input que se deben introducir.
 */
function calculateRequiredInputWithReturn($targetOutputQuantity, $requiredPerCraft, $returnPercentage)
{
    if ($targetOutputQuantity <= 0 || $requiredPerCraft <= 0) return 0;

    $returnFraction = $returnPercentage / 100;

    $checkCanProduce = function($initial_input) use ($targetOutputQuantity, $requiredPerCraft, $returnFraction) {
        $inputAvailable = $initial_input;
        $outputProduced = 0;

        while ($inputAvailable >= $requiredPerCraft) {
            $canCraftThisRound = floor($inputAvailable / $requiredPerCraft);
            $craftThisRound = min($canCraftThisRound, $targetOutputQuantity - $outputProduced);

            if ($craftThisRound <= 0) break;

            $outputProduced += $craftThisRound;
            $consumedInput = $craftThisRound * $requiredPerCraft;
            $inputAvailable -= $consumedInput;
            $return = $consumedInput * $returnFraction;
            $inputAvailable += $return;

            if ($outputProduced >= $targetOutputQuantity) return true;
        }
        return $outputProduced >= $targetOutputQuantity;
    };

    $low = 0;
    // Estimación inicial generosa del límite superior
    $high = $targetOutputQuantity * $requiredPerCraft * 2;

    // Asegurarse de que el límite superior inicial sea suficiente
    while (!$checkCanProduce($high)) {
        $high = $high * 2;
    }
    $minRequired = $high; // Inicializar con un valor que sabemos que funciona

    // Búsqueda binaria para encontrar la cantidad mínima requerida
    $searchLow = 0;
    $searchHigh = $high;

    while ($searchLow <= $searchHigh) {
        $mid = floor(($searchLow + $searchHigh) / 2);
        if ($checkCanProduce($mid)) {
            $minRequired = $mid; // mid puede ser la respuesta, intenta con menos
            $searchHigh = $mid - 1;
        } else {
            $searchLow = $mid + 1; // mid no es suficiente, necesita más
        }
    }

    return $minRequired;
}


/**
 * Helper function to get the previous tier string.
 */
function getPrevTier(string $tier, array $allTiers): ?string
{
    $index = array_search($tier, $allTiers);
    if ($index !== false && $index > 0) {
        return $allTiers[$index - 1];
    }
    return null;
}


// --- Procesar la petición POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir los datos del formulario
    $formData = $_POST; // $_POST ya maneja arrays como selling_prices[], raw_hide_costs[], etc.

    // --- Validación Básica (adaptada del Laravel Validator) ---
    $errors = [];

    // Validación de campos numéricos requeridos
    $numericFields = ['rental_cost', 'purchase_percentage', 'sales_percentage', 'tax_percentage'];
    foreach ($numericFields as $field) {
        if (!isset($formData[$field]) || !is_numeric($formData[$field]) || $formData[$field] < 0) {
            $errors[] = "El campo {$field} es requerido y debe ser un número positivo.";
        }
    }
     // Handle initial_quantities[hides] separately as it's in a nested array
    if (!isset($formData['initial_quantities']['hides']) || !is_numeric($formData['initial_quantities']['hides']) || $formData['initial_quantities']['hides'] < 0) {
        $errors[] = 'El campo Cantidad Inicial de Pieles T2 es requerido y debe ser un número positivo.';
    }


    // Validación de tiers seleccionados
    $validStartingTiers = array_slice($allTiers, 0, -1); // T2 a T7
    $validCraftingTiers = $allTiers; // T2 a T8

    if (!isset($formData['starting_tier']) || !in_array($formData['starting_tier'], $validStartingTiers)) {
        $errors[] = 'El tier de inicio seleccionado es inválido.';
    } else {
         // Obtener el tier de inicio seleccionado para usarlo en validaciones posteriores
         $startingTier = $formData['starting_tier'];
    }


    if (!isset($formData['crafting_limit_tier']) || !in_array($formData['crafting_limit_tier'], $validCraftingTiers)) {
        $errors[] = 'El tier límite de crafteo seleccionado es inválido.';
    } else {
         // Obtener el tier límite de crafteo seleccionado para usarlo en validaciones posteriores
        $craftingLimitTier = $formData['crafting_limit_tier'];
         // Asegurarse de que el tier límite no sea menor que el de inicio
        if (isset($startingTier)) { // Solo validar si el startingTier es válido
            $startIndex = array_search($startingTier, $allTiers);
            $limitIndex = array_search($craftingLimitTier, $allTiers);
            if ($startIndex !== false && $limitIndex !== false && $startIndex > $limitIndex) {
                $errors[] = 'El tier límite de crafteo no puede ser menor que el tier de inicio.';
            }
        }
    }

     // Validación del porcentaje de retorno
    $validReturnPercentages = [36.7, 43.5, 53.9];
    // Convertir el valor recibido a float para la comparación estricta
    $receivedReturnPercentage = isset($formData['return_percentage']) ? (float)$formData['return_percentage'] : null;

    if ($receivedReturnPercentage === null || !in_array($receivedReturnPercentage, $validReturnPercentages)) {
         $errors[] = 'El porcentaje de retorno seleccionado es inválido.';
    }


    // --- Validación de Precios y Costos (AJUSTADA) ---

    // 1. Validar Precio de Venta del Tier Límite
    if (!isset($formData['selling_prices'][$craftingLimitTier]) || !is_numeric($formData['selling_prices'][$craftingLimitTier]) || $formData['selling_prices'][$craftingLimitTier] < 0) {
        $errors[] = "El valor para el precio de venta en el tier {$craftingLimitTier} es inválido o falta.";
    }

    // 2. Validar Costos de Pieles en Bruto para el rango de crafteo
    if (!isset($formData['raw_hide_costs']) || !is_array($formData['raw_hide_costs'])) {
         $errors[] = "Faltan los datos de costos de pieles en bruto.";
    } else {
         $startIndex = array_search($startingTier, $allTiers);
         $limitIndex = array_search($craftingLimitTier, $allTiers);

         // Iterar solo sobre los tiers relevantes para los costos de pieles
         for ($i = $startIndex; $i <= $limitIndex; $i++) {
             $currentTier = $allTiers[$i];
             if (!isset($formData['raw_hide_costs'][$currentTier]) || !is_numeric($formData['raw_hide_costs'][$currentTier]) || $formData['raw_hide_costs'][$currentTier] < 0) {
                 $errors[] = "El valor para los costos de pieles en bruto en el tier {$currentTier} es inválido o falta.";
             }
         }
    }


    // 3. Validar Precio de Compra del Tier Anterior al de Inicio (si aplica)
    // Solo es necesario si el tier de inicio es T3 o superior
    if (isset($startingTier) && $startingTier !== 't2') {
        $prevTierForStarting = getPrevTier($startingTier, $allTiers);
        if ($prevTierForStarting) { // Asegurarse de que getPrevTier no retornó null (debería ser T2 a T6)
            if (!isset($formData['buying_prices'][$prevTierForStarting]) || !is_numeric($formData['buying_prices'][$prevTierForStarting]) || $formData['buying_prices'][$prevTierForStarting] < 0) {
                $errors[] = "El valor para el precio de compra en el tier {$prevTierForStarting} es inválido o falta.";
            }
        }
         // Asegurarse de que el array buying_prices esté presente, aunque solo validemos una key específica
         if (!isset($formData['buying_prices']) || !is_array($formData['buying_prices'])) {
              $errors[] = "Faltan los datos de precios de compra.";
         }
    } else {
         // Si el tier de inicio es T2, no se necesita precio de compra de tier anterior.
         // Aun así, validar que el array buying_prices esté presente, aunque vacío o con otros tiers no relevantes para T2.
         if (!isset($formData['buying_prices']) || !is_array($formData['buying_prices'])) {
              $errors[] = "Faltan los datos de precios de compra.";
         }
         // Podemos opcionalmente validar que si se enviaron otros buying_prices, sean numéricos y no negativos,
         // aunque no son estrictamente necesarios para el cálculo cuando startingTier es T2.
         // Dejaremos esta validación opcional para no ser demasiado estrictos si el frontend envía datos extra.
         /*
         if (isset($formData['buying_prices']) && is_array($formData['buying_prices'])) {
             foreach($formData['buying_prices'] as $tier => $price) {
                 if (!is_numeric($price) || $price < 0) {
                     $errors[] = "Un valor para precio de compra en el tier {$tier} es inválido.";
                     break; // Salir después del primer error en buying_prices
                 }
             }
         }
         */
    }


    // Si hay errores de validación, retornar la respuesta con los errores
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit; // Detener la ejecución
    }

    // --- Fin de Validación AJUSTADA ---


    // --- Procesar los Datos Válidos y Realizar Cálculos (Adaptado del Controlador) ---

    // Asegurarse de que las variables de tier estén definidas después de la validación exitosa
    $startingTier = $formData['starting_tier'];
    $craftingLimitTier = $formData['crafting_limit_tier'];


    $rentalCost = (float) $formData['rental_cost'];
    $purchasePercentage = (float) $formData['purchase_percentage'];
    $salesPercentage = (float) $formData['sales_percentage'];
    $taxPercentage = (float) $formData['tax_percentage'];
    // Asegurarse de que initial_quantities sea un array incluso si solo 'hides' llegó
    $initialQuantities = is_array($formData['initial_quantities']) ? $formData['initial_quantities'] : ['hides' => (float)$formData['initial_quantities']['hides'] ?? 0, 'leather' => 0];
     // Asegurarse de que hides es un número
    $initialQuantities['hides'] = (float)($initialQuantities['hides'] ?? 0);
    // Asegurarse de que leather es 0 o un número (aunque no se usa como input)
    $initialQuantities['leather'] = (float)($initialQuantities['leather'] ?? 0);


    $returnPercentage = (float) $formData['return_percentage'];
    // Asegurarse de que los arrays de precios/costos están inicializados, incluso si faltaron tiers en la validación
    // Esto es para que las funciones de cálculo no fallen al intentar acceder a una key no existente.
    $sellingPrices = is_array($formData['selling_prices']) ? $formData['selling_prices'] : [];
    $rawHideCosts = is_array($formData['raw_hide_costs']) ? $formData['raw_hide_costs'] : [];
    $buyingPrices = is_array($formData['buying_prices']) ? $formData['buying_prices'] : [];


    $returnFraccion = $returnPercentage / 100;
    $purchaseMultiplier = 1 + ($purchasePercentage / 100);

    // Calcular el porcentaje total de reducción basado en el precio original
    $totalSaleTaxPercentage = $salesPercentage + $taxPercentage;
    // El multiplicador para el precio de venta neto
    $netSellingPriceMultiplier = 1 - ($totalSaleTaxPercentage / 100);


    // Arrays para almacenar los resultados por tier
    $craftedQuantity = array_fill_keys($allTiers, 0);
    $requiredRawHides = array_fill_keys($allTiers, 0);
    $requiredLeather = array_fill_keys($allTiers, 0); // Material anterior requerido (cuero)
    $totalCostPerTier = array_fill_keys($allTiers, 0); // Costo total para producir la cantidad de este tier
    $costPerUnit = array_fill_keys($allTiers, 0); // Costo promedio por unidad producida de este tier
    $netSellingPricePerTier = array_fill_keys($allTiers, 0);
    $profitLossAmountPerTier = array_fill_keys($allTiers, 0);
    $profitLossPercentagePerTier = array_fill_keys($allTiers, 0);
    $tierStatus = array_fill_keys($allTiers, 'not_crafted'); // 'profit', 'loss', 'not_crafted'
    $totalRentalCostPerTier = array_fill_keys($allTiers, 0); // Costo total de alquiler para producir este tier
    $rentalCostPerUnit = array_fill_keys($allTiers, 0); // Costo de alquiler por unidad producida


    // Indices para el bucle de crafteo
    $startIndex = array_search($startingTier, $allTiers);
    $limitIndex = array_search($craftingLimitTier, $allTiers);

    // Esto ya se validó arriba, pero por seguridad
    if ($startIndex === false || $limitIndex === false || $startIndex > $limitIndex) {
        echo json_encode(['success' => false, 'error' => 'Rango de crafteo inválido después de la validación.']);
        exit;
    }

    // Amount of leather from the tier BEFORE the current one, available for the current tier's crafting.
    // With the new input model (always raw hides), this will be the output of the raw hide simulation
    // for the starting tier, and then the crafted quantity of the tier below in the loop.
    $availableLeatherFromPreviousTier = 0;


    // --- Bucle de Cálculo para cada Tier dentro del rango seleccionado ---
    for ($i = $startIndex; $i <= $limitIndex; $i++) {
        $currentTier = $allTiers[$i];
        $recipe = $recipes[$currentTier];
        $prevTier = $recipe['prev_tier']; // Tier required as input (e.g., T2 for T3)
        $reqPrev = $recipe['req_prev'];
        $reqRaw = $recipe['req_raw'];
        // Usar el costo de la piel en bruto del tier actual
        $rawCost = ($rawHideCosts[$currentTier] ?? 0) * $purchaseMultiplier;


        // Inicializar valores para el tier actual antes de calcular
        $craftedQuantity[$currentTier] = 0;
        $costOfInputMaterialsForTier = 0;
        $requiredRawHides[$currentTier] = 0;
        $requiredLeather[$currentTier] = 0;
        $totalRentalCostPerTier[$currentTier] = 0;
        $rentalCostPerUnit[$currentTier] = 0;
        $totalCostPerTier[$currentTier] = 0;
        $costPerUnit[$currentTier] = 0;
        $netSellingPricePerTier[$currentTier] = (($sellingPrices[$currentTier] ?? 0) * $netSellingPriceMultiplier);
        $profitLossAmountPerTier[$currentTier] = 0;
        $profitLossPercentagePerTier[$currentTier] = 0;
        $tierStatus[$currentTier] = 'not_crafted';


        $currentTierIndex = array_search($currentTier, $allTiers);
        $startingTierIndex = array_search($startingTier, $allTiers);
        $prevTierIndex = ($prevTier !== null) ? array_search($prevTier, $allTiers) : false;


        if ($currentTier === $startingTier) {
            // Starting tier: Production from initial raw hides of this tier.

            $initialRawHidesForStartingTier = $initialQuantities['hides']; // User input (always raw hides)

            // Calculate crafted quantity of the starting tier leather from initial raw hides
            $producedStartingLeather = calculateLeatherFromRawHides($initialRawHidesForStartingTier, $returnPercentage, $reqRaw);
            $craftedQuantity[$currentTier] = floor($producedStartingLeather);


            if ($craftedQuantity[$currentTier] <= 0) {
                 // If no leather can be produced from initial hides, stop this chain.
                 // The produced leather from this tier is 0, so next iteration will also be 0.
                 $availableLeatherFromPreviousTier = 0;
                 continue; // Pasar al siguiente tier en el bucle
            }

            // Initial hides are the raw hides required for this step
            $requiredRawHides[$currentTier] = $initialRawHidesForStartingTier;

            // Calculate the required quantity of previous tier leather for the crafted output quantity.
            // This is needed for cost calculation, only for tiers > T2.
            if ($startingTierIndex > 0) { // If starting tier is T3 or higher
                 $consumedLeatherForStartingTier = calculateRequiredInputWithReturn($craftedQuantity[$currentTier], $reqPrev, $returnPercentage);
                 $requiredLeather[$currentTier] = $consumedLeatherForStartingTier;
            } else {
                 $requiredLeather[$currentTier] = 0; // T2 doesn't require previous leather
            }


            // --- Cost Calculation for Starting Tier (T2+) ---
            // Cost of initial raw hides
            $costOfConsumedRawHides = $initialRawHidesForStartingTier * $rawCost;


            // Cost of required previous tier leather (only for T3+ starting tiers)
            $costOfConsumedLeather = 0;
            if ($startingTierIndex > 0) { // If starting tier is T3 or higher
                 // When starting at T3+, the required previous tier leather (< starting tier) must be acquired.
                 // Its cost is its BUYING PRICE.
                 // IMPORTANT: Check if $prevTier exists in $buyingPrices array received from frontend
                 $costOfConsumedLeather = ($requiredLeather[$currentTier] ?? 0) * ($buyingPrices[$prevTier] ?? 0);
            }

            $costOfInputMaterialsForTier = $costOfConsumedRawHides + $costOfConsumedLeather;


            // The leather produced from the starting tier's hides becomes the available previous tier material for the *next* tier in the loop.
            // This value will be used as $availableLeatherFromPreviousTier in the NEXT iteration.
            $availableLeatherFromPreviousTier = $craftedQuantity[$currentTier];

        } else {
            // Tiers above the starting tier.
            // Material needed is from prevTier.

            // The available previous tier leather comes from the output of the tier below in the loop ($availableLeatherFromPreviousTier from the previous iteration).
            $availablePrevTierLeatherForCrafting = $availableLeatherFromPreviousTier;

            if ($availablePrevTierLeatherForCrafting < $reqPrev) {
                 // Si no hay suficiente material del tier anterior disponible, no se puede craftear este tier.
                 // El material disponible para el siguiente tier es 0.
                 $availableLeatherFromPreviousTier = 0;
                 continue; // Pasar al siguiente tier en el bucle
            }


            // Crafted quantity calculation for tiers > startingTier (uses available previous tier leather)
            $craftedQuantity[$currentTier] = simulateTierProductionFromPrevious(
                 $availablePrevTierLeatherForCrafting,
                 $reqPrev,
                 $returnPercentage
            );


            if ($craftedQuantity[$currentTier] <= 0) {
                 // If no leather can be produced from available previous tier material, stop this chain.
                 // The produced leather from this tier is 0, so next iteration will also be 0.
                 $availableLeatherFromPreviousTier = 0;
                 continue; // Pasar al siguiente tier en el bucle
            }

            // Calculate required raw hides and previous leather quantity for the crafted quantity.
            $consumedRawHidesForTier = calculateRequiredInputWithReturn($craftedQuantity[$currentTier], $reqRaw, $returnPercentage);
            $requiredRawHides[$currentTier] = $consumedRawHidesForTier;

            $consumedLeatherForTier = calculateRequiredInputWithReturn($craftedQuantity[$currentTier], $reqPrev, $returnPercentage);
            $requiredLeather[$currentTier] = $consumedLeatherForTier;


            // --- Cost Calculation for Tiers > Starting Tier ---
            $costOfConsumedLeather = 0;

            if ($prevTierIndex !== false && $prevTierIndex < $startingTierIndex) {
                 // The previous tier's material is from a tier BELOW the starting tier.
                 // It is NOT produced within this chain from initial raw hides.
                 // Its cost is its BUYING PRICE.
                 // IMPORTANT: Check if $prevTier exists in $buyingPrices array received from frontend
                 $costOfConsumedLeather = ($requiredLeather[$currentTier] ?? 0) * ($buyingPrices[$prevTier] ?? 0);
            } else {
                 // The previous tier's material is from a tier >= the starting tier.
                 // It is either the starting leather (produced from hides) or produced from a tier >= starting tier.
                 // Its cost is its AVERAGE PRODUCTION COST (calculated in previous loop iteration and stored in $costPerUnit[$prevTier]).
                 // IMPORTANT: Check if $prevTier exists in $costPerUnit (from previous iteration results)
                 $costOfConsumedLeather = ($requiredLeather[$currentTier] ?? 0) * ($costPerUnit[$prevTier] ?? 0);
            }


            $costOfConsumedRawHides = ($requiredRawHides[$currentTier] * $rawCost);

            $costOfInputMaterialsForTier = $costOfConsumedLeather + $costOfConsumedRawHides;


            // The leather produced from the current tier becomes available for the next tier in the loop.
            $availableLeatherFromPreviousTier = $craftedQuantity[$currentTier];

        } // End of else (Tiers > startingTier)

        // ... rest of tier calculations (rental cost, totalCostPerTier, costPerUnit, profit/loss) ...

        // Rental cost for current tier
        $buildingValue = $buildingValues[$currentTier] ?? 0;
        // Asegurarse de que $craftedQuantity[$currentTier] no sea cero para evitar división por cero
        $rentalCostForTier = ($craftedQuantity[$currentTier] > 0) ? ($rentalCost / 100) * $buildingValue * 0.1125 * $craftedQuantity[$currentTier] : 0;
        $totalRentalCostPerTier[$currentTier] = $rentalCostForTier;
        $rentalCostPerUnit[$currentTier] = ($craftedQuantity[$currentTier] > 0) ? $rentalCostForTier / $craftedQuantity[$currentTier] : 0;

        // Total cost for current tier (materials + rental)
        $totalCostPerTier[$currentTier] = $costOfInputMaterialsForTier + $totalRentalCostPerTier[$currentTier];

        // Cost per unit (Total cost / Quantity)
        $costPerUnit[$currentTier] = ($craftedQuantity[$currentTier] > 0) ? $totalCostPerTier[$currentTier] / $craftedQuantity[$currentTier] : 0;

        // Profit/Loss calculations
        // $netSellingPricePerTier[$currentTier] was already calculated outside the if/else block
        $profitLossAmountPerTier[$currentTier] = ($netSellingPricePerTier[$currentTier] - ($costPerUnit[$currentTier] ?? 0)) * $craftedQuantity[$currentTier];
        $investmentForPercentage = ($totalCostPerTier[$currentTier] ?? 0);
        $profitLossPercentagePerTier[$currentTier] = ($investmentForPercentage > 0) ? ($profitLossAmountPerTier[$currentTier] / $investmentForPercentage) * 100 : 0;

        // Status
        if ($craftedQuantity[$currentTier] > 0) {
             $tierStatus[$currentTier] = (($netSellingPricePerTier[$currentTier] ?? 0) >= ($costPerUnit[$currentTier] ?? 0)) ? 'profit' : 'loss';
        } else {
             $tierStatus[$currentTier] = 'not_crafted';
        }

    } // End of for loop through tiers


    // --- Preparar los Resultados para la Vista y Calcular Resumen General ---
    $results = [];
    // Inicializar las sumas para el resumen
    $totalRawHideCostSummary = 0; // New: Costo Total en Pieles
    $totalAcquiredLeatherCostSummary = 0; // New: Costo Total en Cuero Adquirido
    $totalMaterialInvestmentSummary = 0; // Suma de los dos anteriores
    $totalRentalCostSummary = 0; // Suma de costos de alquiler por tier
    $totalRevenue = 0; // Ingresos del tier LÍMITE
    // $totalOverallCost no se usa en la fórmula final de Ganancia/Pérdida Neta

    // Recortar el array de tiers para iterar solo sobre los que están en el rango de resultados
    $startIndex = array_search($startingTier, $allTiers); // Asegurarse de usar los valores validados
    $limitIndex = array_search($craftingLimitTier, $allTiers); // Asegurarse de usar los valores validados
    $resultTiers = array_slice($allTiers, $startIndex, $limitIndex - $startIndex + 1);

    // Sumar para el resumen general y preparar resultados individuales por tier
    foreach ($resultTiers as $tier) {
         // Añadir los resultados individuales del tier al array principal $results
        $prevTier = getPrevTier($tier, $allTiers); // Usar la función helper adaptada
        $startingTierIndex = array_search($startingTier, $allTiers); // Asegurarse de usar los valores validados
        $prevTierIndex = ($prevTier !== null) ? array_search($prevTier, $allTiers) : false;

        // Obtener el precio de compra del material anterior para este tier (solo para mostrar, no para cálculo de costo)
        // Solo se muestra si el tier anterior existe Y está por debajo del tier de inicio (material adquirido)
        $buyingPriceForDisplay = 0;
        // Asegurarse de que $buyingPrices está inicializado y $prevTier existe en él antes de acceder
        if ($prevTierIndex !== false && $prevTierIndex < $startingTierIndex && isset($buyingPrices[$prevTier])) {
             $buyingPriceForDisplay = ($buyingPrices[$prevTier] ?? 0);
        }


        $results[$tier] = [
            'quantity' => $craftedQuantity[$tier],
            'required_raw_hides' => (int) round($requiredRawHides[$tier]),
            'required_leather' => (int) round($requiredLeather[$tier]),
            'cost_per_unit' => round($costPerUnit[$tier], 2), // Redondear para mostrar
            'rental_cost_per_unit' => round($rentalCostPerUnit[$tier], 2), // Redondear para mostrar
            'selling_price' => ($sellingPrices[$tier] ?? 0), // Precio de venta original para referencia
            'net_selling_price' => round($netSellingPricePerTier[$tier], 2), // Precio de venta después de tasas, redondear
             'buying_price_prev_tier_material' => round($buyingPriceForDisplay, 2), // Precio de compra del material anterior si aplica, redondeado para mostrar
            'profit_loss_amount' => round($profitLossAmountPerTier[$tier], 2), // Redondear para mostrar
            'profit_loss_percentage' => round($profitLossPercentagePerTier[$tier], 2), // Redondear para mostrar
            'status' => $tierStatus[$tier],
        ];

         // Sumar para el resumen general (para costos totales y alquiler total)
        if (($craftedQuantity[$tier] ?? 0) > 0) { // Asegurarse de que el tier fue crafteado
             // Suma de totalCostPerTier (no se usa en la fórmula final de Ganancia/Pérdida Neta, pero se mantiene por si acaso)
             // $totalOverallCost += ($totalCostPerTier[$tier] ?? 0);
             $totalRentalCostSummary += ($totalRentalCostPerTier[$tier] ?? 0); // Suma de totalRentalCostPerTier
        }
    }

    // --- Calcular la Inversión Total en Materiales Adquiridos (Resumen) ---
    // Dividir en Costo Total en Pieles y Costo Total en Cuero Adquirido.

    // Costo de las Pieles Iniciales del Starting Tier (siempre son pieles)
     // Asegurarse de usar el costo de la piel del StartingTier
     // Y asegurarse de que $rawHideCosts[$startingTier] exista
    $totalRawHideCostSummary += (($initialQuantities['hides'] ?? 0) * (($rawHideCosts[$startingTier] ?? 0) * $purchaseMultiplier));


    // Costo de Pieles en Bruto consumidas para Tiers > StartingTier
    // Iterar desde el tier DESPUÉS del de inicio hasta el límite de crafteo
    for ($i = $startIndex + 1; $i <= $limitIndex; $i++) {
         $currentTier = $allTiers[$i];
         // Solo sumar si el tier fue crafteado en este rango
         if (($craftedQuantity[$currentTier] ?? 0) > 0) {
             // Asegurarse de usar el costo de la piel del currentTier
             // Y asegurarse de que $rawHideCosts[$currentTier] exista
             $totalRawHideCostSummary += ($requiredRawHides[$currentTier] ?? 0) * (($rawHideCosts[$currentTier] ?? 0) * $purchaseMultiplier);
         }
    }

    // Costo de Cuero de Tier Anterior (< StartingTier) que fue requerido y adquirido.
    // Esto ocurre para el STARTING TIER (si T3+) y para tiers > STARTING TIER donde prevTier < startingTier.
    // Iterar por TODOS los tiers en el rango de resultados
    for ($i = $startIndex; $i <= $limitIndex; $i++) {
         $currentTier = $allTiers[$i];
         $recipe = $recipes[$currentTier];
         $prevTier = $recipe['prev_tier'];
         $prevTierIndex = ($prevTier !== null) ? array_search($prevTier, $allTiers) : false;
         $startingTierIndex = array_search($startingTier, $allTiers);


         // Si el tier actual fue crafteado Y (el tier anterior requerido es MENOR que el tier de inicio)
         // significa que el cuero anterior tuvo que ser adquirido.
         // La condición prevTierIndex !== false && prevTierIndex < startingTierIndex es la clave para identificar material adquirido.
         // Se aplica tanto al starting tier (si T3+) como a tiers superiores si su prevTier está por debajo del starting index.
         // Asegurarse de que $prevTier existe en $buyingPrices
         if (($craftedQuantity[$currentTier] ?? 0) > 0 && $prevTierIndex !== false && $prevTierIndex < $startingTierIndex && isset($buyingPrices[$prevTier])) {
              // La cantidad requerida de este cuero anterior (< startingTier) es requiredLeather[$currentTier].
              // Su costo es su precio de compra.
              $totalAcquiredLeatherCostSummary += ($requiredLeather[$currentTier] ?? 0) * ($buyingPrices[$prevTier] ?? 0);
         }
    }


    // La inversión total para la base del porcentaje es la suma de pieles + cuero adquirido
    $totalMaterialInvestmentSummary = $totalRawHideCostSummary + $totalAcquiredLeatherCostSummary;


    // --- Calcular Ingresos Totales (solo del último tier) ---
    $finalTier = $craftingLimitTier;
    // Asegurarse de que el finalTier esté en los resultados antes de acceder a sus valores
    $totalRevenue = isset($results[$finalTier]) ? (($results[$finalTier]['quantity'] ?? 0) * ($results[$finalTier]['net_selling_price'] ?? 0)) : 0;


    // --- Calcular Ganancia/Pérdida Neta y su Porcentaje (nueva fórmula) ---
    // Net Profit/Loss = Revenue (last tier) - Costo Total Materiales Adquiridos - Total Rental Cost
    // Usamos $totalMaterialInvestmentSummary que es la suma de pieles + cuero adquirido
    $netProfitLossSummary = $totalRevenue - $totalMaterialInvestmentSummary - $totalRentalCostSummary;

    // El porcentaje se basa en la inversión total de materiales adquiridos + alquiler total
    $totalInvestmentForPercentage = $totalMaterialInvestmentSummary + $totalRentalCostSummary;
    $netProfitLossPercentageSummary = ($totalInvestmentForPercentage > 0) ? ($netProfitLossSummary / $totalInvestmentForPercentage) * 100 : 0;


    $summary = [
         'total_raw_hide_cost' => round($totalRawHideCostSummary, 2), // Nuevo: Costo Total en Pieles
         'total_acquired_leather_cost' => round($totalAcquiredLeatherCostSummary, 2), // Nuevo: Costo Total en Cuero Adquirido
         'total_material_investment' => round($totalMaterialInvestmentSummary, 2), // Suma de los dos anteriores (para base del porcentaje)
         'total_rental_cost' => round($totalRentalCostSummary, 2), // Suma de costos de alquiler por tier
         'total_revenue' => round($totalRevenue, 2), // Ingresos del TIER LÍMITE
         'net_profit_loss' => round($netProfitLossSummary, 2), // Nueva fórmula
         'net_profit_loss_percentage' => round($netProfitLossPercentageSummary, 2), // Porcentaje basado en Adquirido + Alquiler
    ];

    // Asignar el array summary al array results principal
    $results['summary'] = $summary;


    // Retornar los resultados en formato JSON
    echo json_encode(['success' => true, 'results' => $results]);

} else {
    // Si no es una petición POST, se puede retornar un mensaje de error o la estructura vacía
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
}

?>