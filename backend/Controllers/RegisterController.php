<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Models/Client.php');
require_once(__DIR__ . '/../Contexts/UserContext.php');

class RegisterController
{
    private UserContext $ctx;

    public function __construct(mysqli $db)
    {
        $this->ctx = new UserContext($db);
    }

    public function register(string $fullName, string $email,
                             string $phone, string $password): array
    {
        if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            return ['success' => false, 'message' => 'Заполните обязательные поля'];
        }

        if ($this->ctx->emailExists($email)) {
            return ['success' => false, 'message' => 'Пользователь с таким email уже существует'];
        }

        $ok = $this->ctx->insert(
            $fullName, $email, $phone,
            password_hash($password, PASSWORD_DEFAULT)
        );

        return $ok
            ? ['success' => true,  'message' => 'Регистрация прошла успешно']
            : ['success' => false, 'message' => 'Ошибка регистрации'];
    }
}
