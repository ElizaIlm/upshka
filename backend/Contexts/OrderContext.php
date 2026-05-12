<?php

class OrderContext
{
    public function __construct(private mysqli $db) {}

    public function findAll(): array
    {
        return $this->db->query("
            SELECT
                o.id,
                o.order_datetime,
                o.total_amount,
                o.status,
                c.full_name AS client_name,
                e.full_name AS waiter_name
            FROM orders o
            LEFT JOIN clients   c ON o.client_id   = c.id
            LEFT JOIN employees e ON o.employee_id = e.id
            ORDER BY o.order_datetime DESC
        ")->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id,
                o.order_datetime,
                o.total_amount,
                o.status,
                o.employee_id,
                c.full_name AS client_name,
                e.full_name AS waiter_name
            FROM orders o
            LEFT JOIN clients   c ON o.client_id   = c.id
            LEFT JOIN employees e ON o.employee_id = e.id
            WHERE o.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findItemsByOrderId(int $orderId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.name AS dish_name, oi.quantity, oi.price_at_order
            FROM order_items oi
            JOIN dishes d ON oi.dish_id = d.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.id,
                o.order_datetime,
                o.total_amount,
                o.status,
                COUNT(oi.id) AS items_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.client_id = ?
            GROUP BY o.id
            ORDER BY o.order_datetime DESC
        ");
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findFreeWaiter(): ?int
    {
        $result = $this->db->query("
            SELECT e.id, COUNT(o.id) AS order_count
            FROM employees e
            LEFT JOIN orders o ON e.id = o.employee_id
            WHERE e.role = 'waiter'
            GROUP BY e.id
        ");

        $waiters   = [];
        $minOrders = PHP_INT_MAX;

        while ($row = $result->fetch_assoc()) {
            if ($row['order_count'] < $minOrders) {
                $minOrders = $row['order_count'];
                $waiters   = [$row['id']];
            } elseif ($row['order_count'] == $minOrders) {
                $waiters[] = $row['id'];
            }
        }

        return empty($waiters) ? null : $waiters[array_rand($waiters)];
    }

    public function insertOrder(?int $clientId, int $employeeId, float $total): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO orders (order_datetime, client_id, employee_id, total_amount, status)
            VALUES (NOW(), ?, ?, ?, 'new')
        ");
        $stmt->bind_param('iid', $clientId, $employeeId, $total);
        $stmt->execute();
        return $this->db->insert_id;
    }

    public function insertOrderItem(int $orderId, int $dishId, int $qty, float $price): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO order_items (order_id, dish_id, quantity, price_at_order)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('iiid', $orderId, $dishId, $qty, $price);
        $stmt->execute();
    }

    public function updateStatus(int $orderId, int $waiterId, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE orders SET status = ?
            WHERE id = ? AND employee_id = ?
        ");
        $stmt->bind_param('sii', $status, $orderId, $waiterId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public function beginTransaction(): void { $this->db->begin_transaction(); }
    public function commit(): void           { $this->db->commit(); }
    public function rollback(): void         { $this->db->rollback(); }
}
