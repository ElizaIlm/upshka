<?php

class DishContext
{
    public function __construct(private mysqli $db) {}

    public function findAll(): array
    {
        return $this->db->query(
            "SELECT * FROM dishes WHERE availability = 1 ORDER BY name"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM dishes WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function insert(string $name, string $composition, float $price,
                           string $description, ?string $imagePath): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO dishes (name, composition, price, description, image_path, availability, created_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param('ssdss', $name, $composition, $price, $description, $imagePath);
        return $stmt->execute();
    }

    public function update(int $id, string $name, string $composition, float $price,
                           string $description, int $availability): bool
    {
        $stmt = $this->db->prepare("
            UPDATE dishes
            SET name = ?, composition = ?, price = ?, description = ?, availability = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssdssi', $name, $composition, $price, $description, $availability, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM dishes WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
