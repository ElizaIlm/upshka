<?php

addRoute('POST', '/auth/login', function ($params) {
    global $mysql_connection;
    require_once ROOT . '/backend/Controllers/LoginController.php';

    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $login    = trim($body['login']    ?? $_POST['login']    ?? '');
    $password = trim($body['password'] ?? $_POST['password'] ?? '');

    $ctrl   = new LoginController($mysql_connection);
    $result = $ctrl->login($login, $password);

    if (!$result['success']) {
        Response::error($result['message'], 401);
        return;
    }

    Response::json([
        'success' => true,
        'token'   => session_id(),
        'user'    => Auth::user(),
    ]);
});

addRoute('POST', '/auth/register', function ($params) {
    global $mysql_connection;
    require_once ROOT . '/backend/Controllers/RegisterController.php';

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? $_POST['full_name'] ?? '');
    $email     = trim($body['email']     ?? $_POST['email']     ?? '');
    $phone     = trim($body['phone']     ?? $_POST['phone']     ?? '');
    $password  = trim($body['password']  ?? $_POST['password']  ?? '');

    $ctrl   = new RegisterController($mysql_connection);
    $result = $ctrl->register($full_name, $email, $phone, $password);

    Response::json($result, $result['success'] ? 201 : 400);
});

addRoute('POST', '/auth/logout', function ($params) {
    $_SESSION = [];
    session_destroy();
    Response::json(['success' => true, 'message' => 'Выход выполнен']);
});
