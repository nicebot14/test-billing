<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/19/19
 * Time: 4:11 AM
 */

namespace App\Enum;

final class EventTypeEnum
{
    const TYPE_CHANGE_BALANCE = 'change_balance';
    const TYPE_TRANSFER_BALANCE = 'transfer_balance';
    const TYPE_BLOCK_BALANCE = 'block_balance';
    const TYPE_COMMIT_BLOCKED_BALANCE = 'commit_blocked_balance';
    const TYPE_ROLLBACK_BLOCKED_BALANCE = 'rollback_blocked_balance';
}
