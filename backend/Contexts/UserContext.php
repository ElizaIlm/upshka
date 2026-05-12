<?php

class UserContext
{
    public function __construct(private mysqli $db) {}

    public function findAll(): array
    {
        return $this->db->query(
            "SELECT id, full_name, email, phone, created_at FROM clients ORDER BY full_name"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, full_name, email, phone FROM clients WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE phone = ? OR email = ?");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findOrdersByClientId(int $clientId): array
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

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function emailExistsExcluding(string $email, int $excludeId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
        $stmt->bind_param('si', $email, $excludeId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function phoneExistsExcluding(string $phone, int $excludeId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM clients WHERE phone = ? AND id != ?");
        $stmt->bind_param('si', $phone, $excludeId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function insert(string $fullName, string $email, string $phone,
                           string $passwordHash, string $createdAt = ''): bool
    {
        if (!$createdAt) {
            $createdAt = date('Y-m-d H:i:s');
        }
        $stmt = $this->db->prepare("
            INSERT INTO clients (full_name, email, phone, password_hash, created_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('sssss', $fullName, $email, $phone, $passwordHash, $createdAt);
        return $stmt->execute();
    }

    public function updateWithPassword(int $id, string $fullName, string $email,
                                       string $phone, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients SET full_name = ?, email = ?, phone = ?, password_hash = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssssi', $fullName, $email, $phone, $passwordHash, $id);
        return $stmt->execute();
    }

    public function updateWithoutPassword(int $id, string $fullName,
                                          string $email, string $phone): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients SET full_name = ?, email = ?, phone = ?
            WHERE id = ?
        ");
        $stmt->bind_param('sssi', $fullName, $email, $phone, $id);
        return $stmt->execute();
    }
}
