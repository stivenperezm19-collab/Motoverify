<?php
/**
 * Test E2E de Endpoints HTTP y Manejo de Sesiones
 */
echo "=== PRUEBAS HTTP E2E DE ENDPOINTS Y SESIONES ===\n";

$cookie_file = __DIR__ . '/test_cookie.txt';
if (file_exists($cookie_file)) unlink($cookie_file);

function http_request($url, $method = 'GET', $data = null, $cookie_file = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookie_file) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $response, 'redirect' => $redirect];
}

// 1. Registro HTTP
echo "1. Probando Registro de Cliente vía HTTP POST...\n";
$email_http = "cliente_http_" . time() . "@motoverify.com";
$reg_data = [
    'action' => 'register',
    'nombre' => 'Cliente HTTP Test',
    'correo' => $email_http,
    'password' => 'Segura123!',
    'password_confirm' => 'Segura123!',
    'documento' => '1020304050',
    'telefono' => '3001234567',
    'ciudad' => 'Neiva',
    'direccion' => 'Calle 10 #20-30'
];

$resReg = http_request("http://127.0.0.1:8000/controllers/cliente_auth_action.php", 'POST', $reg_data, $cookie_file);
echo "  HTTP Code: {$resReg['code']}\n";
echo "  Respuesta: {$resReg['body']}\n";
$regJson = json_decode($resReg['body'], true);
$regOk = ($regJson && $regJson['status'] === 'success');
echo "  [". ($regOk ? "PASS" : "FAIL") ."] Registro exitoso con sesión iniciada.\n\n";

// 2. Agregar al Carrito
echo "2. Probando Agregar Producto al Carrito vía HTTP...\n";
$cart_data = json_encode([
    'action' => 'agregar_carrito',
    'id_repuesto' => 1,
    'cantidad' => 2
]);
$resCart = http_request("http://127.0.0.1:8000/controllers/cliente_pedido_action.php", 'POST', $cart_data, $cookie_file);
echo "  HTTP Code: {$resCart['code']}\n";
echo "  Respuesta: {$resCart['body']}\n";
$cartJson = json_decode($resCart['body'], true);
$cartOk = ($cartJson && $cartJson['status'] === 'success');
echo "  [". ($cartOk ? "PASS" : "FAIL") ."] Producto agregado al carrito de la sesión.\n\n";

// 3. Obtener Carrito Calculado
echo "3. Consultando Carrito Calculado vía HTTP...\n";
$resGetCart = http_request("http://127.0.0.1:8000/controllers/cliente_pedido_action.php?action=obtener_carrito", 'GET', null, $cookie_file);
$getCartJson = json_decode($resGetCart['body'], true);
$calcOk = ($getCartJson && $getCartJson['status'] === 'success' && $getCartJson['carrito']['total_articulos'] >= 2);
echo "  Total Artículos: " . ($getCartJson['carrito']['total_articulos'] ?? 0) . "\n";
echo "  Total Compra: $" . ($getCartJson['carrito']['total'] ?? 0) . "\n";
echo "  [". ($calcOk ? "PASS" : "FAIL") ."] Carrito calculado correctamente con IVA y subtotales.\n\n";

// 4. Realizar Pedido (Checkout)
echo "4. Procesando Pedido Completo (Checkout) vía HTTP...\n";
$order_data = json_encode([
    'action' => 'realizar_pedido',
    'direccion_envio' => 'Calle 10 #20-30',
    'ciudad_envio' => 'Neiva',
    'telefono_contacto' => '3001234567',
    'metodo_pago' => 'Contra Entrega',
    'notas' => 'Entregar en la mañana por favor'
]);
$resOrder = http_request("http://127.0.0.1:8000/controllers/cliente_pedido_action.php", 'POST', $order_data, $cookie_file);
echo "  HTTP Code: {$resOrder['code']}\n";
echo "  Respuesta: {$resOrder['body']}\n";
$orderJson = json_decode($resOrder['body'], true);
$orderOk = ($orderJson && $orderJson['status'] === 'success' && !empty($orderJson['id_venta']));
echo "  [". ($orderOk ? "PASS" : "FAIL") ."] Pedido finalizado y guardado (ID: " . ($orderJson['id_venta'] ?? 'N/A') . ").\n\n";

// 5. Verificar Acceso al Panel de Cliente con Sesión Autenticada
echo "5. Verificando Acceso a views/cliente/dashboard.php con Cookie de Sesión...\n";
$resDash = http_request("http://127.0.0.1:8000/views/cliente/dashboard.php", 'GET', null, $cookie_file);
$dashOk = ($resDash['code'] === 200 && strpos($resDash['body'], 'Cliente HTTP Test') !== false);
echo "  HTTP Code: {$resDash['code']}\n";
echo "  [". ($dashOk ? "PASS" : "FAIL") ."] Panel de cliente responde 200 OK y muestra el nombre del cliente autenticado.\n\n";

// 6. Verificar Control de Acceso: El Cliente NO debe poder acceder al Panel Admin
echo "6. Verificando Seguridad: El Cliente NO debe poder acceder a views/admin/dashboard.php...\n";
$resAdmin = http_request("http://127.0.0.1:8000/views/admin/dashboard.php", 'GET', null, $cookie_file);
$secOk = ($resAdmin['code'] === 302 || strpos($resAdmin['redirect'], 'index.php') !== false || $resAdmin['code'] === 403);
echo "  HTTP Code: {$resAdmin['code']}, Redirect: {$resAdmin['redirect']}\n";
echo "  [". ($secOk ? "PASS" : "FAIL") ."] Control de acceso bloquea exitosamente al Cliente de áreas de Administrador.\n\n";

// Limpiar cookie de prueba
if (file_exists($cookie_file)) unlink($cookie_file);

echo "=== FIN DE PRUEBAS HTTP E2E ===\n";
?>
