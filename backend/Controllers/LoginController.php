<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Models/Client.php');
require_once(__DIR__ . '/../Models/Employee.php');
require_once(__DIR__ . '/../Contexts/UserContext.php');
require_once(__DIR__ . '/../Contexts/EmployeeContext.php');

class LoginController
{
    private UserContext     $userCtx;
    private EmployeeContext $empCtx;

    public function __construct(mysqli $db)
    {
        $this->userCtx = new UserContext($db);
        $this->empCtx  = new EmployeeContext($db);
    }

    public function login(string $login, string $password): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($login) || empty($password)) {
            return ['success' => false, 'message' => 'Введите телефон или email и пароль'];
        }

        $client = $this->userCtx->findByLogin($login);
        if ($client && password_verify($password, $client['password_hash'])) {
            $_SESSION['user_type'] = 'client';
            $_SESSION['user_id']   = $client['id'];
            $_SESSION['user_name'] = $client['full_name'];
            return ['success' => true, 'redirect' => '../../index.php'];
        }

        $employee = $this->empCtx->findByLogin($login);
        if ($employee && password_verify($password, $employee['password_hash'])) {
            $_SESSION['user_type'] = 'employee';
            $_SESSION['user_id']   = $employee['id'];
            $_SESSION['user_name'] = $employee['full_name'];
            $_SESSION['role']      = $employee['role'];

            $redirect = match($employee['role']) {
                'waiter'        => '../../pages/employee/waiter.php',
                'administrator' => '../../pages/employee/admin.php',
                default         => '../../index.php',
            };

            return ['success' => true, 'redirect' => $redirect];
        }

        return ['success' => false, 'message' => 'Неверный логин или пароль'];
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: ../../../frontend/index.php');
        exit();
    }
}
