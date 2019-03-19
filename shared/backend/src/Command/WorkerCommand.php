<?php

namespace App\Command;

use App\DTO\Request\BlockBalanceRequest;
use App\DTO\Request\ChangeBalanceRequest;
use App\DTO\Request\TransferBalanceRequest;
use App\DTO\Request\UnblockBalanceRequest;
use App\Enum\EventTypeEnum;
use App\Service\BillingManager;
use App\Service\QueueAdapter;
use Doctrine\DBAL\DBALException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

class WorkerCommand extends Command
{
    protected static $defaultName = 'app:worker';

    private $queueAdapter;
    private $serializer;
    private $billingManager;

    protected function configure()
    {
        $this
            ->setDescription('Worker')
        ;
    }

    public function __construct(QueueAdapter $queueAdapter,
                                SerializerInterface $serializer,
                                BillingManager $billingManager,
                                $name = null)
    {
        $this->queueAdapter = $queueAdapter;
        $this->serializer = $serializer;
        $this->billingManager = $billingManager;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $this->queueAdapter->pull(QueueAdapter::CHANNEL_REQUEST, function($event, $ack, $reject) use ($io) {
            $data = json_decode($event, true);
            if (!isset($data['type'])) {
                $ack();
                return;
            }

            switch ($data['type']) {
                case EventTypeEnum::TYPE_CHANGE_BALANCE:
                    $this->changeBalanceHandler($event, $data, $io, $ack, $reject);
                    break;
                case EventTypeEnum::TYPE_TRANSFER_BALANCE:
                    $this->transferBalanceHandler($event, $data, $io, $ack, $reject);
                    break;
                case EventTypeEnum::TYPE_BLOCK_BALANCE:
                    $this->blockBalanceHandler($event, $data, $io, $ack, $reject);
                    break;
                case EventTypeEnum::TYPE_ROLLBACK_BLOCKED_BALANCE:
                    $this->unblockBalanceHandler($event, $data, $io, $ack, $reject);
                    break;
                case EventTypeEnum::TYPE_COMMIT_BLOCKED_BALANCE:
                    $this->unblockBalanceHandler($event, $data, $io, $ack, $reject);
                    break;
                default:
                    $ack();
            }
        });
    }

    private function genericHandler(callable $callback, array $data, SymfonyStyle $io, callable $ack, callable $reject)
    {
        try {
            $success = $callback();
        } catch (DBALException $e) {
            $reject();
            return;
        } catch (\Exception $e) {
            $io->error('Error: '. $e->getMessage());
            $data['status'] = 'error';
            $data['error_message'] = $e->getMessage();

            $this->queueAdapter->send(QueueAdapter::CHANNEL_RESPONSE, $data);
            $ack();
            return;
        }

        if ($success) {
            $io->success('Success');
            $data['status'] = 'success';
            $this->queueAdapter->send(QueueAdapter::CHANNEL_RESPONSE, $data);
            $ack();
        } else {
            $io->text('Waiting');
            $reject();
        }
    }

    private function changeBalanceHandler(string $event, array $data, SymfonyStyle $io, callable $ack, callable $reject)
    {
        /**
         * @var $dto ChangeBalanceRequest
         */
        $dto = $this->serializer->deserialize($event, ChangeBalanceRequest::class, 'json');

        $this->genericHandler(function() use($dto) {
            return $this->billingManager->changeBalance($dto);
        }, $data, $io, $ack, $reject);
    }

    private function transferBalanceHandler(string $event, array $data, SymfonyStyle $io, callable $ack, callable $reject)
    {
        /**
         * @var $dto TransferBalanceRequest
         */
        $dto = $this->serializer->deserialize($event, TransferBalanceRequest::class, 'json');

        $this->genericHandler(function() use($dto) {
            return $this->billingManager->transferBalance($dto);
        }, $data, $io, $ack, $reject);
    }

    private function blockBalanceHandler(string $event, array $data, SymfonyStyle $io, callable $ack, callable $reject)
    {
        /**
         * @var $dto BlockBalanceRequest
         */
        $dto = $this->serializer->deserialize($event, BlockBalanceRequest::class, 'json');

        $this->genericHandler(function() use($dto) {
            return $this->billingManager->blockBalance($dto);
        }, $data, $io, $ack, $reject);
    }

    private function unblockBalanceHandler(string $event, array $data, SymfonyStyle $io, callable $ack, callable $reject)
    {
        /**
         * @var $dto UnblockBalanceRequest
         */
        $dto = $this->serializer->deserialize($event, UnblockBalanceRequest::class, 'json');

        $this->genericHandler(function() use($dto) {
            return $this->billingManager->unblockBalance($dto);
        }, $data, $io, $ack, $reject);
    }
}
