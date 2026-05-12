<?php

addRoute('GET', '/dishes', function ($params) {
    global $mysql_connection;
    require_once ROOT . '/backend/Controllers/DishController.php';

    $ctrl   = new DishController($mysql_connection);
    $dishes = $ctrl->getAllDishes();

    Response::json(array_map(fn($d) => [
        'id'           => (int)$d->id,
        'name'         => $d->name,
        'composition'  => $d->composition,
        'price'        => (float)$d->price,
        'description'  => $d->description,
        'image_path'   => $d->image_path,
        'availability' => (bool)$d->availability,
        'created_at'   => $d->created_at,
    ], $dishes));
});

addRoute('GET', '/dishes/{id}', function ($params) {
    global $mysql_connection;
    require_once ROOT . '/backend/Controllers/DishController.php';

    $ctrl = new DishController($mysql_connection);
    $dish = $ctrl->getDishById((int)$params['id']);

    if (!$dish) {
        Response::error('Блюдо не найдено', 404);
        return;
    }

    Response::json([
        'id'           => (int)$dish->id,
        'name'         => $dish->name,
        'composition'  => $dish->composition,
        'price'        => (float)$dish->price,
        'description'  => $dish->description,
        'image_path'   => $dish->image_path,
        'availability' => (bool)$dish->availability,
        'created_at'   => $dish->created_at,
    ]);
});

addRoute('POST', '/dishes', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Controllers/DishController.php';

    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $name        = trim($body['name']        ?? $_POST['name']        ?? '');
    $composition = trim($body['composition'] ?? $_POST['composition'] ?? '');
    $price       = (float)($body['price']    ?? $_POST['price']       ?? 0);
    $description = trim($body['description'] ?? $_POST['description'] ?? '');
    $image       = $_FILES['image'] ?? null;

    if (!$name || !$price) {
        Response::error('Поля name и price обязательны', 422);
        return;
    }

    $ctrl = new DishController($mysql_connection);
    $ok   = $ctrl->createDish($name, $composition, $price, $description, $image);

    Response::json(
        ['success' => $ok, 'message' => $ok ? 'Блюдо создано' : 'Ошибка создания блюда'],
        $ok ? 201 : 500
    );
});

addRoute('PUT', '/dishes/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Controllers/DishController.php';

    $body         = json_decode(file_get_contents('php://input'), true) ?? [];
    $name         = trim($body['name']          ?? '');
    $composition  = trim($body['composition']   ?? '');
    $price        = (float)($body['price']      ?? 0);
    $description  = trim($body['description']   ?? '');
    $availability = (int)($body['availability'] ?? 1);

    if (!$name || !$price) {
        Response::error('Поля name и price обязательны', 422);
        return;
    }

    $ctrl = new DishController($mysql_connection);
    $ok   = $ctrl->updateDish((int)$params['id'], $name, $composition, $price, $description, $availability);

    Response::json(['success' => $ok, 'message' => $ok ? 'Блюдо обновлено' : 'Ошибка обновления блюда']);
});

addRoute('DELETE', '/dishes/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Controllers/DishController.php';

    $ctrl = new DishController($mysql_connection);
    $ok   = $ctrl->deleteDish((int)$params['id']);

    Response::json(['success' => $ok, 'message' => $ok ? 'Блюдо удалено' : 'Ошибка удаления блюда']);
});
