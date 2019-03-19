<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/18/19
 * Time: 6:43 PM
 */

namespace App\Service;

use App\DTO\Request\BlockBalanceRequest;
use App\DTO\Request\ChangeBalanceRequest;
use App\DTO\Request\TransferBalanceRequest;
use App\DTO\Request\UnblockBalanceRequest;
use App\Exception\BillingException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class BillingManager
{
    private $entityManager;
    private $con;
    private $lockManager;
    private $userManager;
    private $transfersManager;

    public function __construct(EntityManagerInterface $entityManager,
                                UserManager $userManager,
                                TransfersManager $transfersManager,
                                LockManager $lockManager)
    {
        $this->entityManager = $entityManager;
        $this->lockManager = $lockManager;
        $this->userManager = $userManager;
        $this->transfersManager = $transfersManager;
        $this->con = $this->entityManager->getConnection();
    }

    /**
     * @param ChangeBalanceRequest $request
     * @return bool
     * @throws BillingException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function changeBalance(ChangeBalanceRequest $request): bool
    {
        $this->con->beginTransaction();
        $userId = $request->userId;

        $locked = $this->lockManager->tryLockUser($userId);
        if (!$locked) {
            return false;
        }

        $userArr = $this->getUserOrThrow($userId);

        bcscale(2);
        $currentBalance = $userArr['balance'];
        $newBalance = bcadd($currentBalance, $request->amount);

        try {
            $this->transfersManager->insertEvent($userId,
                $request->type, $request->operationId, $request->amount);
        } catch(UniqueConstraintViolationException $e) {
            $this->con->rollBack();
            return true;
        }

        // сначала мы пытаемся заинсертить нашу таблицу ивентов т.к. возможен повтор события
        // и только потом проверяем баланс
        $this->checkNewBalance($newBalance, $userId);

        $this->userManager->updateBalance($userId, $newBalance);

        $this->con->commit();
        return true;
    }

    /**
     * @param TransferBalanceRequest $request
     * @return bool
     * @throws BillingException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function transferBalance(TransferBalanceRequest $request): bool
    {
        $this->con->beginTransaction();
        $userId = $request->userId;
        $fromUserId = $request->fromUserId;

        $userLocked = $this->lockManager->tryLockUser($userId);
        if (!$userLocked) {
            return false;
        }

        $fromUserLocked = $this->lockManager->tryLockUser($fromUserId);
        if (!$fromUserLocked) {
            $this->con->rollBack();
            return false;
        }

        $userArr = $this->getUserOrThrow($userId);
        $fromUserArr = $this->getUserOrThrow($fromUserId);

        bcscale(2);
        $userBalance = $userArr['balance'];
        $fromUserBalance = $fromUserArr['balance'];
        $userNewBalance = bcadd($userBalance, $request->amount);
        $fromUserNewBalance = bcsub($fromUserBalance, $request->amount);

        try {
            $this->transfersManager->insertEvent($userId,
                $request->type, $request->operationId, $request->amount, $fromUserId);
        } catch(UniqueConstraintViolationException $e) {
            $this->con->rollBack();
            return true;
        }

        $this->checkNewBalance($fromUserNewBalance, $fromUserId);

        $this->userManager->updateBalance($userId, $userNewBalance);
        $this->userManager->updateBalance($fromUserId, $fromUserNewBalance);

        $this->con->commit();
        return true;
    }

    /**
     * @param BlockBalanceRequest $request
     * @return bool
     * @throws BillingException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function blockBalance(BlockBalanceRequest $request): bool
    {
        $this->con->beginTransaction();
        $userId = $request->userId;

        $locked = $this->lockManager->tryLockUser($userId);
        if (!$locked) {
            return false;
        }

        $userArr = $this->getUserOrThrow($userId);

        bcscale(2);
        $currentBalance = $userArr['balance'];
        $newBalance = bcsub($currentBalance, $request->amount);

        try {
            $this->transfersManager->insertEvent($userId,
                $request->type, $request->operationId, $request->amount);
        } catch(UniqueConstraintViolationException $e) {
            $this->con->rollBack();
            return true;
        }

        $this->checkNewBalance($newBalance, $userId);

        $this->userManager->blockBalance($userId, $newBalance, $request->amount, $request->blockId, $userArr);

        $this->con->commit();
        return true;
    }

    /**
     * @param UnblockBalanceRequest $request
     * @return bool
     * @throws BillingException
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unblockBalance(UnblockBalanceRequest $request): bool
    {
        $this->con->beginTransaction();
        $userId = $request->userId;

        $locked = $this->lockManager->tryLockUser($userId);
        if (!$locked) {
            return false;
        }

        $userArr = $this->getUserOrThrow($userId);

        try {
            $this->transfersManager->insertEvent($userId,
                $request->type, $request->operationId);
        } catch(UniqueConstraintViolationException $e) {
            $this->con->rollBack();
            return true;
        }

        $this->userManager->unblockBalance($request->userId, $request->blockId, $request->commit, $userArr);

        $this->con->commit();
        return true;
    }

    /**
     * @param int $userId
     * @return array|null
     * @throws BillingException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getUserOrThrow(int $userId)
    {
        $userArr = $this->userManager->getUserById($userId);
        if (!$userArr) {
            $this->con->rollBack();
            throw new BillingException("User with id: {$userId} not found");
        }

        return $userArr;
    }

    /**
     * @param string $newBalance
     * @param int $userId
     * @throws BillingException
     * @throws \Doctrine\DBAL\ConnectionException
     */
    private function checkNewBalance(string $newBalance, int $userId)
    {
        if (floatval($newBalance) < 0) {
            $this->con->rollBack();
            throw new BillingException("Balance for user $userId can not be negative. New balance: {$newBalance}");
        }
    }
}
