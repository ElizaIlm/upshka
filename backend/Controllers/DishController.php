<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Models/Dish.php');
require_once(__DIR__ . '/../Contexts/DishContext.php');

class DishController
{
    private DishContext $ctx;

    public function __construct(mysqli $db)
    {
        $this->ctx = new DishContext($db);
    }

    public function getAllDishes(): array
    {
        return array_map(fn($row) => new Dish($row), $this->ctx->findAll());
    }

    public function getDishById(int $id): ?Dish
    {
        $row = $this->ctx->findById($id);
        return $row ? new Dish($row) : null;
    }

    public function createDish(string $name, string $composition, float $price,
                               string $description, ?array $image): bool
    {
        $imagePath = null;
        if ($image && $image['error'] === 0) {
            $uploadDir = __DIR__ . '/../../frontend/img/';
            $fileName  = time() . '_' . basename($image['name']);
            move_uploaded_file($image['tmp_name'], $uploadDir . $fileName);
            $imagePath = 'img/' . $fileName;
        }

        return $this->ctx->insert($name, $composition, $price, $description, $imagePath);
    }

    public function updateDish(int $id, string $name, string $composition, float $price,
                               string $description, int $availability): bool
    {
        return $this->ctx->update($id, $name, $composition, $price, $description, $availability);
    }

    public function deleteDish(int $id): bool
    {
        return $this->ctx->delete($id);
    }
}
