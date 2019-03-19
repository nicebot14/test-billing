<?php
/**
 * Created by PhpStorm.
 * User: nicebot14
 * Date: 3/18/19
 * Time: 5:30 PM
 */

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class BlockBalanceRequest
{
    /**
     * @var int
     *
     * @Assert\Type(type="int")
     * @Assert\NotNull()
     */
    public $userId;

    /**
     * @var string
     *
     * @Assert\Uuid()
     * @Assert\NotNull()
     */
    public $operationId;

    /**
     * @var string
     *
     * @Assert\Uuid()
     * @Assert\NotNull()
     */
    public $blockId;

    /**
     * @var string
     *
     * @Assert\Type(type="numeric")
     * @Assert\NotNull()
     * @Assert\Range(min="1")
     */
    public $amount;

    /**
     * @var string
     *
     */
    public $type;
}
