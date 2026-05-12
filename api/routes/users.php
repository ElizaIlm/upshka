<?php

addRoute('GET', '/users', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $result  = $mysql_connection->query("SELECT id, full_name, email, phone, created_at FROM clients ORDER BY full_name");
    Response::json($result->fetch_all(MYSQLI_ASSOC));
});

addRoute('POST', '/users', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Controllers/UserController.php';

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? '');
    $email     = trim($body['email']     ?? '');
    $phone     = trim($body['phone']     ?? '');
    $password  = trim($body['password']  ?? '');

    $ctrl   = new UserController($mysql_connection);
    $result = $ctrl->createUser($full_name, $email, $phone, $password);

    Response::json($result, $result['success'] ? 201 : 400);
});

addRoute('GET', '/users/{id}', function ($params) {
    global $mysql_connection;
    $auth = Auth::requireAuth();
    $id   = (int)$params['id'];

    // Клиент может видеть только свой профиль
    if ($auth['type'] === 'client' && $auth['id'] !== $id) {
        Response::error('Доступ запрещён', 403);
        return;
    }

    require_once ROOT . '/backend/Controllers/UserController.php';
    $ctrl = new UserController($mysql_connection);
    $user = $ctrl->getUserById($id);

    if (!$user) {
        Response::error('Пользователь не найден', 404);
        return;
    }

    Response::json($user);
});

addRoute('PUT', '/users/{id}', function ($params) {
    global $mysql_connection;
    $auth = Auth::requireAuth();
    $id   = (int)$params['id'];

    // Клиент может редактировать только свой профиль
    if ($auth['type'] === 'client' && $auth['id'] !== $id) {
        Response::error('Доступ запрещён', 403);
        return;
    }

    require_once ROOT . '/backend/Controllers/UserController.php';

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? '');
    $email     = trim($body['email']     ?? '');
    $phone     = trim($body['phone']     ?? '');
    $password  = $body['password']       ?? null;

    $ctrl   = new UserController($mysql_connection);
    $result = $ctrl->updateProfile($id, $full_name, $email, $phone, $password ?: null);

    Response::json($result, $result['success'] ? 200 : 400);
});
