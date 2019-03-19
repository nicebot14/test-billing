<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/19/19
 * Time: 4:45 AM
 */

namespace App\Service;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    private $con;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->con = $entityManager->getConnection();
    }

    /**
     * @param int $userId
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getUserById(int $userId)
    {
        $stmt = $this->con->prepare('SELECT * FROM users WHERE id = :user_id');
        $stmt->bindValue('user_id', $userId);
        $stmt->execute();
        $userArr = $stmt->fetch(FetchMode::ASSOCIATIVE);

        return (array) $userArr ?? null;
    }

    /**
     * @param int $userId
     * @param string $newBalance
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateBalance(int $userId, string $newBalance)
    {
        $stmt = $this->con->prepare('UPDATE users SET balance = :new_balance WHERE id = :user_id ');
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('new_balance', $newBalance);
        $stmt->execute();
    }

    /**
     * @param int $userId
     * @param string $newBalance
     * @param string $blockAmount
     * @param string $blockId
     * @param array|null $user
     * @throws \Doctrine\DBAL\DBALException
     */
    public function blockBalance(int $userId, string $newBalance, string $blockAmount, string $blockId, ?array $user = null)
    {
        if (!$user) {
            $user = $this->getUserById($userId);
        }

        bcscale(2);
        $blockedBalance = bcadd($user['blocked_balance'], $blockAmount);

        $stmt = $this->con->prepare('UPDATE users SET balance = :new_balance, blocked_balance = :block_amount WHERE id = :user_id ');
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('new_balance', $newBalance);
        $stmt->bindValue('block_amount', $blockedBalance);
        $stmt->execute();

        $stmt = $this->con->prepare('INSERT INTO blocked_balances (id, user_id, amount) VALUES (:block_id, :user_id, :amount)');
        $stmt->bindValue('block_id', $blockId);
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('amount', $blockAmount);
        $stmt->execute();
    }

    /**
     * @param int $userId
     * @param string $blockId
     * @param bool $commit
     * @param array|null $user
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unblockBalance(int $userId, string $blockId, bool $commit, ?array $user = null)
    {
        $stmt = $this->con->prepare('SELECT * FROM blocked_balances WHERE id = :block_id AND user_id = :user_id');
        $stmt->bindValue('user_id', $userId);
        $stmt->bindValue('block_id', $blockId);
        $stmt->execute();
        $blockedRecord = $stmt->fetch(FetchMode::ASSOCIATIVE);

        if (!$user) {
            $user = $this->getUserById($userId);
        }

        bcscale(2);
        $blockedBalance = bcsub($user['blocked_balance'], $blockedRecord['amount']);

        if ($commit) {
            $stmt = $this->con->prepare('UPDATE users SET blocked_balance = :blocked_balance WHERE id = :user_id ');
            $stmt->bindValue('user_id', $userId);
            $stmt->bindValue('blocked_balance', $blockedBalance);
            $stmt->execute();
        } else {
            $newBalance = bcadd($user['balance'], $blockedRecord['amount']);
            $stmt = $this->con->prepare('UPDATE users SET balance = :new_balance, blocked_balance = :blocked_balance WHERE id = :user_id ');
            $stmt->bindValue('user_id', $userId);
            $stmt->bindValue('blocked_balance', $blockedBalance);
            $stmt->bindValue('new_balance', $newBalance);
            $stmt->execute();
        }

        $stmt = $this->con->prepare('DELETE FROM blocked_balances WHERE id = :block_id');
        $stmt->bindValue('block_id', $blockId);
        $stmt->execute();
    }
}
