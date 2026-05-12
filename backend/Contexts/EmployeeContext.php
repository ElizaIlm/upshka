<?php

class EmployeeContext
{
    public function __construct(private mysqli $db) {}

    public function findAll(): array
    {
        return $this->db->query(
            "SELECT id, full_name, login, role FROM employees ORDER BY full_name"
        )->fetch_all(MYSQLI_ASSOC);
    }

    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE login = ?");
        $stmt->bind_param('s', $login);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function insert(string $fullName, string $login,
                           string $passwordHash, string $role): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO employees (full_name, login, password_hash, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('ssss', $fullName, $login, $passwordHash, $role);
        return $stmt->execute();
    }

    public function update(int $id, string $fullName, string $role): bool
    {
        $stmt = $this->db->prepare("
            UPDATE employees SET full_name = ?, role = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $fullName, $role, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
