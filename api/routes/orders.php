<?php

// NOTE: /orders/my and /orders/waiter must be registered before /orders/{id}
// to prevent "my"/"waiter" being matched as a numeric id.

addRoute('GET', '/orders', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $result = $mysql_connection->query("
        SELECT o.id, o.order_datetime, o.total_amount, o.status,
               c.full_name AS client_name, e.full_name AS waiter_name
        FROM orders o
        LEFT JOIN clients  c ON o.client_id  = c.id
        LEFT JOIN employees e ON o.employee_id = e.id
        ORDER BY o.order_datetime DESC
    ");

    Response::json($result->fetch_all(MYSQLI_ASSOC));
});

addRoute('GET', '/orders/my', function ($params) {
    global $mysql_connection;
    $user = Auth::requireClient();
    require_once ROOT . '/backend/Controllers/UserController.php';

    $ctrl   = new UserController($mysql_connection);
    $orders = $ctrl->getUserOrders($user['id']);

    Response::json($orders);
});

addRoute('GET', '/orders/{id}', function ($params) {
    global $mysql_connection;
    $user    = Auth::requireAuth();
    $orderId = (int)$params['id'];

    $stmt = $mysql_connection->prepare("
        SELECT o.id, o.order_datetime, o.total_amount, o.status, o.employee_id,
               c.full_name AS client_name, e.full_name AS waiter_name
        FROM orders o
        LEFT JOIN clients  c ON o.client_id  = c.id
        LEFT JOIN employees e ON o.employee_id = e.id
        WHERE o.id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        Response::error('Заказ не найден', 404);
        return;
    }

    // Waiter sees only own orders; client sees own orders; admin sees all
    if ($user['role'] === 'waiter' && (int)$order['employee_id'] !== $user['id']) {
        Response::error('Доступ запрещён', 403);
        return;
    }

    $stmt = $mysql_connection->prepare("
        SELECT d.name AS dish_name, oi.quantity, oi.price_at_order
        FROM order_items oi
        JOIN dishes d ON oi.dish_id = d.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    Response::json($order);
});

addRoute('POST', '/orders', function ($params) {
    global $mysql_connection;
    $user = Auth::requireClient();
    require_once ROOT . '/backend/Controllers/OrderController.php';

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $cart = $body['cart'] ?? [];

    // Expected: cart = [{dish_id, qty, price}, ...]
    $normalized = [];
    foreach ($cart as $item) {
        $id = (int)($item['dish_id'] ?? $item['id'] ?? 0);
        if (!$id) continue;
        $normalized[$id] = ['qty' => (int)$item['qty'], 'price' => (float)$item['price']];
    }

    if (empty($normalized)) {
        Response::error('Корзина пуста', 422);
        return;
    }

    $ctrl     = new OrderController($mysql_connection);
    $order_id = $ctrl->createOrder($user['id'], $normalized);

    if (!$order_id) {
        Response::error('Ошибка создания заказа', 500);
        return;
    }

    Response::json(['success' => true, 'order_id' => $order_id], 201);
});

addRoute('POST', '/orders/waiter', function ($params) {
    global $mysql_connection;
    $user = Auth::requireEmployee();
    require_once ROOT . '/backend/Controllers/OrderController.php';

    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $cart  = $body['cart'] ?? [];

    // Expected: cart = [{id, qty, price}, ...]
    $items = [];
    foreach ($cart as $item) {
        $id = (int)($item['id'] ?? $item['dish_id'] ?? 0);
        if (!$id) continue;
        $items[] = [
            'id'    => $id,
            'qty'   => (int)$item['qty'],
            'price' => (float)$item['price'],
        ];
    }

    if (empty($items)) {
        Response::error('Корзина пуста', 422);
        return;
    }

    $ctrl     = new OrderController($mysql_connection);
    $order_id = $ctrl->createOrderByWaiter($user['id'], $items);

    if (!$order_id) {
        Response::error('Ошибка создания заказа', 500);
        return;
    }

    Response::json(['success' => true, 'order_id' => $order_id], 201);
});

addRoute('PATCH', '/orders/{id}/status', function ($params) {
    global $mysql_connection;
    $user = Auth::requireEmployee();
    require_once ROOT . '/backend/Controllers/OrderController.php';

    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $status = $body['status'] ?? '';

    if (!in_array($status, OrderController::allowedStatuses(), true)) {
        Response::error('Недопустимый статус. Допустимые: ' . implode(', ', OrderController::allowedStatuses()), 422);
        return;
    }

    $ctrl = new OrderController($mysql_connection);
    $ok   = $ctrl->updateStatusForWaiter((int)$params['id'], $user['id'], $status);

    Response::json(['success' => $ok, 'message' => $ok ? 'Статус обновлён' : 'Ошибка обновления статуса']);
});
