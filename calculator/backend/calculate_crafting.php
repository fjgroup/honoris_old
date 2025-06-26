<?php

header('Content-Type: application/json'); // Asegurar que la respuesta sea JSON

// Permitir solicitudes desde cualquier origen (CORS) - Ajustar segun necesidad
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Capturar y decodificar los datos JSON enviados desde el frontend
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Array para almacenar errores de validacion
$errors = [];

// --- Validacion de Datos (Backend) ---
// Validar que los datos necesarios existan y tengan el tipo correcto

if (!isset($data['rental_cost_value']) || !is_numeric($data['rental_cost_value']) || $data['rental_cost_value'] < 0) { // Cambiado de rental_cost_percentage a rental_cost_value
    $errors[] = "El valor del costo de alquiler es invalido.";
}
if (!isset($data['purchase_percentage']) || !is_numeric($data['purchase_percentage']) || $data['purchase_percentage'] < 0) {
    $errors[] = "La tasa de compra es invalida.";
}
if (!isset($data['sales_percentage']) || !is_numeric($data['sales_percentage']) || $data['sales_percentage'] < 0) {
    $errors[] = "La tasa de venta es invalida.";
}
// La tasa de publicacion ahora es fija (2.5%), pero el backend recibira el total de costos hundidos
// if (!isset($data['publication_percentage']) || !is_numeric($data['publication_percentage']) || $data['publication_percentage'] < 0) {
//     $errors[] = "La tasa de publicacion es invalida.";
// }
// Validar la tasa de retorno
if (!isset($data['return_percentage']) || !is_numeric($data['return_percentage']) || $data['return_percentage'] <= 0) {
     $errors[] = "La tasa de retorno es invalida.";
}


if (!isset($data['product_name']) || empty(trim($data['product_name']))) {
    $errors[] = "El nombre del producto no puede estar vacio.";
}
if (!isset($data['object_power']) || !is_numeric($data['object_power']) || $data['object_power'] <= 0) {
    $errors[] = "El poder del objeto es invalido.";
}
if (!isset($data['crafted_units']) || !is_numeric($data['crafted_units']) || $data['crafted_units'] < 1) {
    $errors[] = "La cantidad a fabricar debe ser al menos 1.";
}

// Validar ingredientes (nombre y cantidad)
if (!isset($data['ingredients']) || !is_array($data['ingredients']) || count($data['ingredients']) === 0) {
    $errors[] = "Debe definir al menos un ingrediente.";
} else {
    foreach ($data['ingredients'] as $index => $ingredient) {
        if (!isset($ingredient['name']) || empty(trim($ingredient['name']))) {
            $errors[] = "El nombre del ingrediente " . ($index + 1) . " no puede estar vacio.";
        }
        if (!isset($ingredient['quantity']) || !is_numeric($ingredient['quantity']) || $ingredient['quantity'] <= 0) {
            $errors[] = "La cantidad del ingrediente " . ($index + 1) . " es invalida.";
        }
    }
}

// Validar precios de ingredientes (solo si la seccion de precios esta implicada)
// La validacion frontend verifica si la seccion esta visible antes de enviar precios invalidos.
// Aqui, nos aseguramos de que si se enviaron precios, sean validos.
if (isset($data['ingredient_prices']) && is_array($data['ingredient_prices'])) {
    foreach ($data['ingredient_prices'] as $index => $price_item) {
        if (!isset($price_item['name']) || empty(trim($price_item['name']))) {
             // Esto no deberia ocurrir si el frontend envia el nombre del hidden input, pero es una seguridad
             $errors[] = "Falta el nombre para el precio del ingrediente " . ($index + 1) . ".";
        }
        if (!isset($price_item['price']) || !is_numeric($price_item['price']) || $price_item['price'] < 0) {
             $errors[] = "El precio del ingrediente '" . ($price_item['name'] ?? 'Desconocido') . "' es invalido.";
        }
    }
    // Opcional: Validar que el numero de precios coincida con el numero de ingredientes definidos
     if (count($data['ingredients']) !== count($data['ingredient_prices'])) {
         $errors[] = "El numero de precios de ingredientes recibidos no coincide con el numero de ingredientes definidos.";
     }

} else {
     // Si no se enviaron precios de ingredientes, y hay ingredientes definidos, es un error para el calculo de costos.
     // Esto deberia ser validado en frontend, pero lo confirmamos aqui.
     if (isset($data['ingredients']) && is_array($data['ingredients']) && count($data['ingredients']) > 0) {
          $errors[] = "No se recibieron los precios de los ingredientes para el calculo.";
     }
}


// Validar precio de venta del producto
if (!isset($data['product_selling_price']) || !is_numeric($data['product_selling_price']) || $data['product_selling_price'] < 0) {
    $errors[] = "El precio de venta del producto es invalido.";
}
if (!isset($data['fabrication_cycles']) || !is_numeric($data['fabrication_cycles']) || $data['fabrication_cycles'] < 1) {
    $errors[] = "La cantidad de lotes de fabricacion es invalida.";
}
if (!isset($data['total_sunk_publication_cost_to_deduct']) || !is_numeric($data['total_sunk_publication_cost_to_deduct']) || $data['total_sunk_publication_cost_to_deduct'] < 0) {
    $errors[] = "El total de costos de publicacion hundidos es invalido.";
}



// Si hay errores de validacion, devolver respuesta de error
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'errors' => $errors
    ]);
    exit(); // Detener ejecucion
}

// --- Calculos (Backend) ---
// Si la validacion pasa, proceder con los calculos

// Convertir datos a tipos numericos
$rental_cost_value = (float)$data['rental_cost_value']; // Cambiado de rental_cost_percentage
$purchase_percentage = (float)$data['purchase_percentage'];
$sales_percentage = (float)$data['sales_percentage'];
$fixed_publication_percentage = 2.5; // Tasa fija
$return_percentage = (float)$data['return_percentage']; // Tasa de retorno

$object_power = (int)$data['object_power'];
$crafted_units = (int)$data['crafted_units'];
$ingredients = $data['ingredients'];
$ingredient_prices = $data['ingredient_prices'] ?? []; // Usar array vacio si no hay precios (no deberia ocurrir si frontend valida)
$product_selling_price = (float)$data['product_selling_price'];
$fabrication_cycles = (int)$data['fabrication_cycles'];
$total_sunk_publication_cost_to_deduct = (float)$data['total_sunk_publication_cost_to_deduct'];


// Calcular costo total de ingredientes (considerando tasa de retorno)
$total_ingredient_cost_before_purchase_tax = 0;
$ingredient_prices_map = [];
// Crear un mapa de precios de ingredientes para facil acceso por nombre
foreach ($ingredient_prices as $price_item) {
     if (isset($price_item['name'], $price_item['price'])) {
          $ingredient_prices_map[$price_item['name']] = (float)$price_item['price'];
     }
}

foreach ($ingredients as $ingredient) {
    $ingredient_name = $ingredient['name'];
    // $required_quantity_for_all_crafted_units es la cantidad total del ingrediente para TODAS las unidades crafteadas
    $required_quantity_for_all_crafted_units = (int)$ingredient['quantity'];

    // Buscar el precio de este ingrediente en el mapa
    $ingredient_price_per_unit = $ingredient_prices_map[$ingredient_name] ?? 0; // Usar 0 si el precio no se encuentra

    // Si la tasa de retorno es 36.7%, compras 100% - 36.7% = 63.3% de lo necesario.
    $effective_purchase_rate = (100 - $return_percentage) / 100;

    // Cantidad de este ingrediente que realmente necesitas comprar para TODAS las unidades crafteadas, despues del retorno
    $actual_quantity_to_buy_for_all_units = $required_quantity_for_all_crafted_units * $effective_purchase_rate;

    // Costo de este ingrediente para TODAS las unidades crafteadas (despues de retorno, antes de tasa de compra)
    $cost_of_this_ingredient_for_all_units = $actual_quantity_to_buy_for_all_units * $ingredient_price_per_unit;

    // Sumar al costo total de ingredientes (antes de la tasa de compra)
    $total_ingredient_cost_before_purchase_tax += $cost_of_this_ingredient_for_all_units;

    // error_log("DEBUG: Ingrediente: {$ingredient_name}, Cantidad Requerida Total: {$required_quantity_for_all_crafted_units}, Precio Unitario: {$ingredient_price_per_unit}, Retorno(%): {$return_percentage}, Tasa Efectiva Compra: {$effective_purchase_rate}, Cantidad Real a Comprar Total: {$actual_quantity_to_buy_for_all_units}, Costo Ingrediente Total (post-retorno): {$cost_of_this_ingredient_for_all_units}");
}
// error_log("DEBUG: Costo Total Ingredientes (post-retorno, pre-tasa compra): {$total_ingredient_cost_before_purchase_tax}");

// Aplicar tasa de compra al costo total de ingredientes (que ya considera la tasa de retorno)
// Si la tasa de compra es 2.5%, el costo real es 100% + 2.5% = 102.5%
$total_ingredient_cost_with_purchase_tax = $total_ingredient_cost_before_purchase_tax * (1 + ($purchase_percentage / 100));
// error_log("DEBUG: Costo Total Ingredientes (1 ciclo, post-retorno, post-tasa compra): {$total_ingredient_cost_with_purchase_tax}");

// Calcular costo total de alquiler (formula actualizada para tratar rental_cost_value como %)
$single_cycle_rental_cost = ($rental_cost_value / 100) * ($crafted_units * $object_power) * 0.1125;
// error_log("DEBUG: Costo Alquiler (1 ciclo): {$single_cycle_rental_cost}");

// Costo total de fabricacion para UN CICLO (materiales + alquiler)
$single_cycle_crafting_cost = $total_ingredient_cost_with_purchase_tax + $single_cycle_rental_cost;
// error_log("DEBUG: Costo Fabricacion (1 ciclo): {$single_cycle_crafting_cost}");

// Ingresos totales por venta para UN CICLO (sin tasas aun)
$single_cycle_sales_revenue = $product_selling_price * $crafted_units;
// error_log("DEBUG: Ingresos Venta Brutos (1 ciclo): {$single_cycle_sales_revenue}");

// Calcular resultados por unidad
// $crafted_units es la cantidad de items producidos en UN ciclo/lote
$per_unit_ingredient_cost = ($crafted_units > 0) ? $total_ingredient_cost_with_purchase_tax / $crafted_units : 0;
$per_unit_rental_cost = ($crafted_units > 0) ? $single_cycle_rental_cost / $crafted_units : 0;
$per_unit_total_crafting_cost = ($crafted_units > 0) ? $single_cycle_crafting_cost / $crafted_units : 0;

// Ingresos netos por venta para UN CICLO (despues de tasas de venta y publicacion)
$single_cycle_sales_deduction = $single_cycle_sales_revenue * ($sales_percentage / 100);
$single_cycle_publication_deduction = $single_cycle_sales_revenue * ($fixed_publication_percentage / 100); // Usar tasa fija para el costo de la publicacion actual
$single_cycle_net_sales_revenue = $single_cycle_sales_revenue - $single_cycle_sales_deduction - $single_cycle_publication_deduction;
// error_log("DEBUG: Ingresos Venta Netos (1 ciclo): {$single_cycle_net_sales_revenue}");

$per_unit_net_selling_price = ($crafted_units > 0) ? $single_cycle_net_sales_revenue / $crafted_units : 0;

// Ganancia/Perdida Neta para UN CICLO
$single_cycle_net_profit_loss = $single_cycle_net_sales_revenue - $single_cycle_crafting_cost;
// error_log("DEBUG: Ganancia/Perdida Neta (1 ciclo): {$single_cycle_net_profit_loss}");

$per_unit_net_profit_loss = ($crafted_units > 0) ? $single_cycle_net_profit_loss / $crafted_units : 0;

// --- Aplicar el multiplicador de Lotes de Fabricacion a los totales ---
$summary_total_ingredient_cost = $total_ingredient_cost_with_purchase_tax * $fabrication_cycles;
$summary_total_rental_cost = $single_cycle_rental_cost * $fabrication_cycles;
$summary_total_crafting_cost = $single_cycle_crafting_cost * $fabrication_cycles;
$summary_total_sales_revenue_gross = $single_cycle_sales_revenue * $fabrication_cycles;

// Deducciones para el total de ciclos
$summary_sales_deduction = $summary_total_sales_revenue_gross * ($sales_percentage / 100);
$summary_current_publication_cost = $single_cycle_publication_deduction * $fabrication_cycles; // Costo de la publicacion actual para todos los lotes

$summary_total_net_sales_revenue = $summary_total_sales_revenue_gross - $summary_sales_deduction - $summary_current_publication_cost - $total_sunk_publication_cost_to_deduct;
$summary_net_profit_loss = $summary_total_net_sales_revenue - $summary_total_crafting_cost;

// Calcular porcentajes de ganancia/perdida
// Porcentaje basado en el costo total de fabricacion
$net_profit_loss_percentage = ($summary_total_crafting_cost > 0) ? ($summary_net_profit_loss / $summary_total_crafting_cost) * 100 : ($summary_net_profit_loss >= 0 ? 0 : -100); // Evitar division por cero

// Porcentaje de ganancia/perdida por unidad basado en el costo por unidad
$per_unit_net_profit_loss_percentage = ($per_unit_total_crafting_cost > 0) ? ($per_unit_net_profit_loss / $per_unit_total_crafting_cost) * 100 : ($per_unit_net_profit_loss >= 0 ? 0 : -100);


// Preparar resultados para devolver
$results = [
    'summary' => [
        'total_ingredient_cost' => $summary_total_ingredient_cost,
        'total_rental_cost' => $summary_total_rental_cost,
        'total_crafting_cost' => $summary_total_crafting_cost,
        'total_sales_revenue_gross' => $summary_total_sales_revenue_gross,
        'current_total_publication_cost' => $summary_current_publication_cost, // Costo de la publicacion actual
        'total_sunk_publication_cost_applied' => $total_sunk_publication_cost_to_deduct, // Suma de costos hundidos aplicados
        'total_net_sales_revenue' => $summary_total_net_sales_revenue,
        'net_profit_loss' => $summary_net_profit_loss, // Este ya no se muestra, pero se usa para el %
        'net_profit_loss_percentage' => $net_profit_loss_percentage
    ],
    'per_unit' => [
        'ingredient_cost' => $per_unit_ingredient_cost,
        'rental_cost' => $per_unit_rental_cost,
        'total_crafting_cost' => $per_unit_total_crafting_cost,
        'selling_price' => $product_selling_price, // Precio de venta original por unidad
        'net_selling_price' => $per_unit_net_selling_price, // Precio de venta neto por unidad (despues de tasas)
        'net_profit_loss' => $per_unit_net_profit_loss,
        'net_profit_loss_percentage' => $per_unit_net_profit_loss_percentage
    ],
    // Puedes anadir detalles de ingredientes si es necesario para mostrar en el frontend
    // 'ingredient_details' => ...
];

// Devolver respuesta de exito con los resultados
echo json_encode([
    'success' => true,
    'results' => $results
]);

?>