<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Contexts/UserContext.php');

class UserController
{
    private UserContext $ctx;

    public function __construct(mysqli $db)
    {
        $this->ctx = new UserContext($db);
    }

    public function getUserById(int $id): ?array
    {
        return $this->ctx->findById($id);
    }

    public function getUserOrders(int $clientId): array
    {
        return $this->ctx->findOrdersByClientId($clientId);
    }

    public function updateProfile(int $id, string $fullName, string $email,
                                  string $phone, ?string $password = null): array
    {
        if (empty($fullName)) {
            return ['success' => false, 'message' => 'ФИО не может быть пустым'];
        }

        if (!empty($email) && $this->ctx->emailExistsExcluding($email, $id)) {
            return ['success' => false, 'message' => 'Этот email уже используется'];
        }

        if (!empty($phone) && $this->ctx->phoneExistsExcluding($phone, $id)) {
            return ['success' => false, 'message' => 'Этот телефон уже используется'];
        }

        if ($password) {
            $ok = $this->ctx->updateWithPassword(
                $id, $fullName, $email, $phone,
                password_hash($password, PASSWORD_DEFAULT)
            );
        } else {
            $ok = $this->ctx->updateWithoutPassword($id, $fullName, $email, $phone);
        }

        return $ok
            ? ['success' => true,  'message' => 'Профиль успешно обновлён']
            : ['success' => false, 'message' => 'Ошибка обновления профиля'];
    }

    public function createUser(string $fullName, string $email,
                               string $phone, string $password): array
    {
        if (empty($fullName) || empty($password)) {
            return ['success' => false, 'message' => 'Введите имя и пароль'];
        }

        $ok = $this->ctx->insert(
            $fullName, $email, $phone,
            password_hash($password, PASSWORD_DEFAULT)
        );

        return $ok
            ? ['success' => true,  'message' => 'Пользователь создан']
            : ['success' => false, 'message' => 'Ошибка создания пользователя'];
    }
}
