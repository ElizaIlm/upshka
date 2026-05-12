<?php

class StatsContext
{
    public function __construct(private mysqli $db) {}

    public function getDashboard(): array
    {
        $today = date('Y-m-d');

        return [
            'dishes_total'  => (int)$this->scalar("SELECT COUNT(*) FROM dishes"),
            'orders_today'  => (int)$this->scalar("SELECT COUNT(*) FROM orders WHERE DATE(order_datetime) = '$today'"),
            'orders_total'  => (int)$this->scalar("SELECT COUNT(*) FROM orders"),
            'clients_total' => (int)$this->scalar("SELECT COUNT(*) FROM clients"),
            'waiters_total' => (int)$this->scalar("SELECT COUNT(*) FROM employees WHERE role = 'waiter'"),
            'revenue_today' => (float)$this->scalar("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_datetime) = '$today'"),
            'revenue_month' => (float)$this->scalar("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(order_datetime) = MONTH(NOW()) AND YEAR(order_datetime) = YEAR(NOW())"),
        ];
    }

    public function getReport(): array
    {
        $today = date('Y-m-d');

        return [
            'revenue_today' => (float)$this->scalar("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(order_datetime) = '$today'"),
            'revenue_month' => (float)$this->scalar("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE MONTH(order_datetime) = MONTH(NOW()) AND YEAR(order_datetime) = YEAR(NOW())"),
            'orders_today'  => (int)$this->scalar("SELECT COUNT(*) FROM orders WHERE DATE(order_datetime) = '$today'"),
            'orders_total'  => (int)$this->scalar("SELECT COUNT(*) FROM orders"),
            'top_dishes'    => $this->getTopDishes(),
            'top_waiters'   => $this->getTopWaiters(),
        ];
    }

    public function getTopDishes(int $limit = 5): array
    {
        return $this->db->query("
            SELECT d.name, SUM(oi.quantity) AS total
            FROM order_items oi
            JOIN dishes d ON d.id = oi.dish_id
            GROUP BY d.id
            ORDER BY total DESC
            LIMIT $limit
        ")->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopWaiters(int $limit = 5): array
    {
        return $this->db->query("
            SELECT e.full_name, COUNT(o.id) AS orders_count
            FROM orders o
            JOIN employees e ON e.id = o.employee_id
            WHERE e.role = 'waiter'
            GROUP BY e.id
            ORDER BY orders_count DESC
            LIMIT $limit
        ")->fetch_all(MYSQLI_ASSOC);
    }

    private function scalar(string $sql): mixed
    {
        return $this->db->query($sql)->fetch_row()[0];
    }
}
