<?php

require_once(__DIR__ . '/../../settings/connect_database.php');
require_once(__DIR__ . '/../Contexts/OrderContext.php');

class OrderController
{
    private OrderContext $ctx;

    public static function allowedStatuses(): array
    {
        return ['new', 'preparing', 'ready', 'completed'];
    }

    public static function statusLabelRu(string $status): string
    {
        $map = [
            'new'       => 'Новый',
            'preparing' => 'Готовится',
            'ready'     => 'Готов к подаче',
            'completed' => 'Завершён',
        ];
        return $map[$status] ?? $status;
    }

    public function __construct(mysqli $db)
    {
        $this->ctx = new OrderContext($db);
    }

    public function createOrder(int $clientId, array $cart): int|false
    {
        if (empty($cart)) {
            return false;
        }

        $this->ctx->beginTransaction();

        try {
            $total      = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
            $employeeId = $this->ctx->findFreeWaiter();

            if (!$employeeId) {
                throw new Exception('Нет доступных официантов');
            }

            $orderId = $this->ctx->insertOrder($clientId, $employeeId, $total);

            foreach ($cart as $dishId => $item) {
                $this->ctx->insertOrderItem($orderId, (int)$dishId, (int)$item['qty'], (float)$item['price']);
            }

            $this->ctx->commit();
            return $orderId;

        } catch (Exception) {
            $this->ctx->rollback();
            return false;
        }
    }

    public function createOrderByWaiter(int $employeeId, array $cart): int|false
    {
        if (empty($cart)) {
            return false;
        }

        $this->ctx->beginTransaction();

        try {
            $total   = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart));
            $orderId = $this->ctx->insertOrder(null, $employeeId, $total);

            foreach ($cart as $item) {
                $this->ctx->insertOrderItem(
                    $orderId,
                    (int)$item['id'],
                    (int)$item['qty'],
                    (float)$item['price']
                );
            }

            $this->ctx->commit();
            return $orderId;

        } catch (Exception) {
            $this->ctx->rollback();
            return false;
        }
    }

    public function updateStatusForWaiter(int $orderId, int $waiterId, string $status): bool
    {
        if (!in_array($status, self::allowedStatuses(), true)) {
            return false;
        }
        return $this->ctx->updateStatus($orderId, $waiterId, $status);
    }
}
