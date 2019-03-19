<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/19/19
 * Time: 4:40 AM
 */

namespace App\Service;

use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;

class LockManager
{
    const ADVISORY_LOCK_KEY_USER = 10;

    private $con;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->con = $entityManager->getConnection();
    }

    /**
     * @param int $userId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function tryLockUser(int $userId): bool
    {
        $stmt = $this->con->prepare('SELECT pg_try_advisory_xact_lock(:key, :user_id) as lock');
        $stmt->bindValue('key', self::ADVISORY_LOCK_KEY_USER);
        $stmt->bindValue('user_id', $userId);

        $stmt->execute();
        $locked = $stmt->fetch(FetchMode::ASSOCIATIVE)['lock'];

        return (bool) $locked;
    }
}
