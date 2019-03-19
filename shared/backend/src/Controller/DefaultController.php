<?php

namespace App\Controller;

use App\DTO\Request\BlockBalanceRequest;
use App\DTO\Request\ChangeBalanceRequest;
use App\DTO\Request\TransferBalanceRequest;
use App\DTO\Request\UnblockBalanceRequest;
use App\Enum\EventTypeEnum;
use App\Service\QueueAdapter;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DefaultController extends AbstractController
{
    /**
     * @Route("/balance", name="balance", methods={"POST"})
     */
    public function balance(Request $request,
                            SerializerInterface $serializer,
                            ValidatorInterface $validator,
                            UserManager $userManager,
                            QueueAdapter $queueAdapter)
    {
        $json = $request->getContent();
        /**
         * @var $dto ChangeBalanceRequest
         */
        $dto = $serializer->deserialize($json, ChangeBalanceRequest::class, 'json');
        $dto->type = EventTypeEnum::TYPE_CHANGE_BALANCE;

        $violations = $validator->validate($dto);

        if ($violations->count()) {
            return $this->json($violations, 400);
        }

        $userId = $dto->userId;
        $user = $userManager->getUserById($userId);
        if (!$user) {
            throw new BadRequestHttpException();
        }

        $queueAdapter->send(QueueAdapter::CHANNEL_REQUEST, $dto);

        return $this->json(['success']);
    }

    /**
     * @Route("/transfer", name="transfer", methods={"POST"})
     */
    public function transfer(Request $request,
                             SerializerInterface $serializer,
                             ValidatorInterface $validator,
                             UserManager $userManager,
                             QueueAdapter $queueAdapter)
    {
        $json = $request->getContent();
        /**
         * @var $dto TransferBalanceRequest
         */
        $dto = $serializer->deserialize($json, TransferBalanceRequest::class, 'json');
        $dto->type = EventTypeEnum::TYPE_TRANSFER_BALANCE;

        $violations = $validator->validate($dto);

        if ($violations->count()) {
            return $this->json($violations, 400);
        }

        $user1 = $userManager->getUserById($dto->userId);
        if (!$user1) {
            throw new BadRequestHttpException();
        }

        $user2 = $userManager->getUserById($dto->fromUserId);
        if (!$user2) {
            throw new BadRequestHttpException();
        }

        $queueAdapter->send(QueueAdapter::CHANNEL_REQUEST, $dto);

        return $this->json(['success']);
    }

    /**
     * @Route("/block", name="block", methods={"POST"})
     */
    public function block(Request $request,
                          SerializerInterface $serializer,
                          ValidatorInterface $validator,
                          UserManager $userManager,
                          QueueAdapter $queueAdapter)
    {
        $json = $request->getContent();
        /**
         * @var $dto BlockBalanceRequest
         */
        $dto = $serializer->deserialize($json, BlockBalanceRequest::class, 'json');
        $dto->type = EventTypeEnum::TYPE_BLOCK_BALANCE;

        $violations = $validator->validate($dto);

        if ($violations->count()) {
            return $this->json($violations, 400);
        }

        $user = $userManager->getUserById($dto->userId);
        if (!$user) {
            throw new BadRequestHttpException();
        }

        $queueAdapter->send(QueueAdapter::CHANNEL_REQUEST, $dto);

        return $this->json(['success']);
    }

    /**
     * @Route("/unblock", name="unblock", methods={"POST"})
     */
    public function unblock(Request $request,
                          SerializerInterface $serializer,
                          ValidatorInterface $validator,
                          UserManager $userManager,
                          QueueAdapter $queueAdapter)
    {
        $json = $request->getContent();
        /**
         * @var $dto UnblockBalanceRequest
         */
        $dto = $serializer->deserialize($json, UnblockBalanceRequest::class, 'json');
        if ($dto->commit) {
            $dto->type = EventTypeEnum::TYPE_COMMIT_BLOCKED_BALANCE;
        } else {
            $dto->type = EventTypeEnum::TYPE_ROLLBACK_BLOCKED_BALANCE;
        }

        $violations = $validator->validate($dto);

        if ($violations->count()) {
            return $this->json($violations, 400);
        }

        $user = $userManager->getUserById($dto->userId);
        if (!$user) {
            throw new BadRequestHttpException();
        }

        $queueAdapter->send(QueueAdapter::CHANNEL_REQUEST, $dto);

        return $this->json(['success']);
    }
}
