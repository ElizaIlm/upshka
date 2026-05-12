<?php

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id'   => (int)$_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'type' => $_SESSION['user_type'],
            'role' => $_SESSION['role'] ?? null,
        ];
    }

    public static function requireAuth(): array
    {
        $user = self::user();
        if (!$user) {
            Response::error('Необходима авторизация', 401);
            exit;
        }
        return $user;
    }

    public static function requireAdmin(): array
    {
        $user = self::requireAuth();
        if ($user['role'] !== 'administrator') {
            Response::error('Доступ запрещён: требуется роль администратора', 403);
            exit;
        }
        return $user;
    }

    public static function requireEmployee(): array
    {
        $user = self::requireAuth();
        if ($user['type'] !== 'employee') {
            Response::error('Доступ запрещён: требуется роль сотрудника', 403);
            exit;
        }
        return $user;
    }

    public static function requireClient(): array
    {
        $user = self::requireAuth();
        if ($user['type'] !== 'client') {
            Response::error('Доступ запрещён: требуется роль клиента', 403);
            exit;
        }
        return $user;
    }
}
