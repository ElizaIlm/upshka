<?php

addRoute('GET', '/employees', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $result = $mysql_connection->query("SELECT id, full_name, login, role FROM employees ORDER BY full_name");
    Response::json($result->fetch_all(MYSQLI_ASSOC));
});

addRoute('POST', '/employees', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

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

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysql_connection->prepare("INSERT INTO employees (full_name, login, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $full_name, $login, $hash, $role);
    $ok = $stmt->execute();

    Response::json(
        ['success' => $ok, 'message' => $ok ? 'Сотрудник создан' : 'Ошибка создания сотрудника'],
        $ok ? 201 : 500
    );
});

addRoute('PUT', '/employees/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $full_name = trim($body['full_name'] ?? '');
    $role      = $body['role']           ?? 'waiter';

    if (!$full_name) {
        Response::error('Поле full_name обязательно', 422);
        return;
    }

    $id   = (int)$params['id'];
    $stmt = $mysql_connection->prepare("UPDATE employees SET full_name = ?, role = ? WHERE id = ?");
    $stmt->bind_param('ssi', $full_name, $role, $id);
    $ok = $stmt->execute();

    Response::json(['success' => $ok, 'message' => $ok ? 'Сотрудник обновлён' : 'Ошибка обновления сотрудника']);
});

addRoute('DELETE', '/employees/{id}', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $id   = (int)$params['id'];
    $stmt = $mysql_connection->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();

    Response::json(['success' => $ok, 'message' => $ok ? 'Сотрудник удалён' : 'Ошибка удаления сотрудника']);
});
