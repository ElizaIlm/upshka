<?php

addRoute('GET', '/orders', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/OrderContext.php';

    $ctx = new OrderContext($mysql_connection);
    Response::json($ctx->findAll());
});

addRoute('GET', '/orders/my', function ($params) {
    global $mysql_connection;
    $user = Auth::requireClient();
    require_once ROOT . '/backend/Contexts/UserContext.php';

    $ctx = new UserContext($mysql_connection);
    Response::json($ctx->findOrdersByClientId($user['id']));
});

addRoute('GET', '/orders/{id}', function ($params) {
    global $mysql_connection;
    $user = Auth::requireAuth();
    require_once ROOT . '/backend/Contexts/OrderContext.php';

    $ctx     = new OrderContext($mysql_connection);
    $orderId = (int)$params['id'];
    $order   = $ctx->findById($orderId);

    if (!$order) {
        Response::error('Заказ не найден', 404);
        return;
    }

    if ($user['role'] === 'waiter' && (int)$order['employee_id'] !== $user['id']) {
        Response::error('Доступ запрещён', 403);
        return;
    }

    $order['items'] = $ctx->findItemsByOrderId($orderId);
    Response::json($order);
});

addRoute('POST', '/orders', function ($params) {
    global $mysql_connection;
    $user = Auth::requireClient();
    require_once ROOT . '/backend/Controllers/OrderController.php';

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $cart = $body['cart'] ?? [];

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
    $items = [];

    foreach ($body['cart'] ?? [] as $item) {
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
