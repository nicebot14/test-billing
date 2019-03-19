<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/19/19
 * Time: 4:51 AM
 */

namespace App\Service;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class TransfersManager
{
    private $con;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->con = $entityManager->getConnection();
    }

    /**
     * @param int $userId
     * @param string $type
     * @param string $operationId
     * @param null|string $amount
     * @param null|string $fromUserId
     * @throws \Doctrine\DBAL\DBALException
     * @throws UniqueConstraintViolationException
     */
    public function insertEvent(int $userId, string $type, string $operationId,
                                ?string $amount = null, ?string $fromUserId = null)
    {
        $stmt = $this->con->prepare('INSERT INTO transfers 
                (user_id, type, operation_id, amount, from_user_id) VALUES 
                (:user_id, :type, :operation_id, :amount, :from_user_id)');
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('type', $type);
        $stmt->bindValue('operation_id', $operationId);
        $stmt->bindValue('amount', $amount);
        $stmt->bindValue('from_user_id', $fromUserId);
        $stmt->execute();
    }
}
