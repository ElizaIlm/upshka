<?php

addRoute('GET', '/employees', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/EmployeeContext.php';

    $ctx = new EmployeeContext($mysql_connection);
    Response::json($ctx->findAll());
});

addRoute('POST', '/employees', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/EmployeeContext.php';

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? '');
    $login     = trim($body['login']     ?? '');
    $password  = trim($body['password']  ?? '');
    $role      = $body['role']           ?? 'waiter';

    if (!$full_name || !$login || !$password) {
        Response::error('Поля full_name, login и password обязательны', 422);
        return;
    }

    if (!in_array($role, ['administrator', 'waiter'], true)) {
        Response::error('Недопустимая роль. Допустимые: administrator, waiter', 422);
        return;
    }

    $ctx = new EmployeeContext($mysql_connection);
    $ok  = $ctx->insert($full_name, $login, password_hash($password, PASSWORD_DEFAULT), $role);

    Response::json(
        ['success' => $ok, 'message' => $ok ? 'Сотрудник создан' : 'Ошибка создания сотрудника'],
        $ok ? 201 : 500
    );
});

addRoute('PUT', '/employees/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/EmployeeContext.php';

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? '');
    $role      = $body['role']           ?? 'waiter';

    if (!$full_name) {
        Response::error('Поле full_name обязательно', 422);
        return;
    }

    $ctx = new EmployeeContext($mysql_connection);
    $ok  = $ctx->update((int)$params['id'], $full_name, $role);

    Response::json(['success' => $ok, 'message' => $ok ? 'Сотрудник обновлён' : 'Ошибка обновления сотрудника']);
});

addRoute('DELETE', '/employees/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();
    require_once ROOT . '/backend/Contexts/EmployeeContext.php';

    $ctx = new EmployeeContext($mysql_connection);
    $ok  = $ctx->delete((int)$params['id']);

    Response::json(['success' => $ok, 'message' => $ok ? 'Сотрудник удалён' : 'Ошибка удаления сотрудника']);
});
