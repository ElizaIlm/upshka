<?php

addRoute('GET', '/stats', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $today = date('Y-m-d');

    $dishes_total  = (int)$mysql_connection->query("SELECT COUNT(*) FROM dishes")->fetch_row()[0];
    $orders_today  = (int)$mysql_connection->query("SELECT COUNT(*) FROM orders WHERE DATE(order_datetime) = '$today'")->fetch_row()[0];
    $orders_total  = (int)$mysql_connection->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
    $clients_total = (int)$mysql_connection->query("SELECT COUNT(*) FROM clients")->fetch_row()[0];
    $waiters_total = (int)$mysql_connection->query("SELECT COUNT(*) FROM employees WHERE role = 'waiter'")->fetch_row()[0];
    $revenue_today = (float)$mysql_connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_datetime) = '$today'")->fetch_row()[0];
    $revenue_month = (float)$mysql_connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(order_datetime) = MONTH(NOW()) AND YEAR(order_datetime) = YEAR(NOW())")->fetch_row()[0];

    Response::json([
        'dishes_total'  => $dishes_total,
        'orders_today'  => $orders_today,
        'orders_total'  => $orders_total,
        'clients_total' => $clients_total,
        'waiters_total' => $waiters_total,
        'revenue_today' => $revenue_today,
        'revenue_month' => $revenue_month,
    ]);
});

addRoute('GET', '/reports', function ($params) {
    global $mysql_connection;
    Auth::requireAdmin();

    $today = date('Y-m-d');

    $revenue_today = (float)$mysql_connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_datetime) = '$today'")->fetch_row()[0];
    $revenue_month = (float)$mysql_connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(order_datetime) = MONTH(NOW()) AND YEAR(order_datetime) = YEAR(NOW())")->fetch_row()[0];
    $orders_today  = (int)$mysql_connection->query("SELECT COUNT(*) FROM orders WHERE DATE(order_datetime) = '$today'")->fetch_row()[0];
    $orders_total  = (int)$mysql_connection->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];

    $top_dishes = $mysql_connection->query("
        SELECT d.name, SUM(oi.quantity) AS total
        FROM order_items oi
        JOIN dishes d ON d.id = oi.dish_id
        GROUP BY d.id
        ORDER BY total DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    $top_waiters = $mysql_connection->query("
        SELECT e.full_name, COUNT(o.id) AS orders_count
        FROM orders o
        JOIN employees e ON e.id = o.employee_id
        WHERE e.role = 'waiter'
        GROUP BY e.id
        ORDER BY orders_count DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    Response::json([
        'revenue_today' => $revenue_today,
        'revenue_month' => $revenue_month,
        'orders_today'  => $orders_today,
        'orders_total'  => $orders_total,
        'top_dishes'    => $top_dishes,
        'top_waiters'   => $top_waiters,
    ]);
});
