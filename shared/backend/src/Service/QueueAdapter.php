<?php

namespace App\Service;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 *
 * Class QueueAdapter
 * @package App\Service
 */
class QueueAdapter
{
    const CHANNEL_REQUEST = 'request';
    const CHANNEL_RESPONSE = 'response';

    /**
     * @var AMQPChannel
     */
    private $channel;

    public function __construct()
    {
        $rabbitHost = getenv('RABBIT_HOST');
        $rabbitPort = getenv('RABBIT_PORT');
        $rabbitUser = getenv('RABBIT_USER');
        $rabbitPassword = getenv('RABBIT_PASSWORD');
        $connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPassword);
        $this->channel = $connection->channel();
    }

    private function declareChannel(string $channelName): void
    {
        $this->channel->queue_declare(
            $channelName,    #queue name - Имя очереди может содержать до 255 байт UTF-8 символов
            false,        #passive - может использоваться для проверки того, инициирован ли обмен, без того, чтобы изменять состояние сервера
            true,        #durable - убедимся, что RabbitMQ никогда не потеряет очередь при падении - очередь переживёт перезагрузку брокера
            false,        #exclusive - используется только одним соединением, и очередь будет удалена при закрытии соединения
            false        #autodelete - очередь удаляется, когда отписывается последний подписчик
        );
    }

    public function send(string $channelName, $data): void
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $msg = new AMQPMessage($data, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $this->declareChannel($channelName);
        $this->channel->basic_publish($msg, '', $channelName);
    }

    public function pull(string $channelName, callable $callback)
    {
        $this->declareChannel($channelName);
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($channelName, '', false, false, false, false, function($msg) use($callback) {
            $event = $msg->body;
            $callback($event, function() use($msg) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }, function() use($msg) {
                $msg->delivery_info['channel']->basic_reject($msg->delivery_info['delivery_tag'], true);
            });
        });

        try {
            while (count($this->channel->callbacks)) {
                $this->channel->wait(null, true);
            }
        } catch (AMQPTimeoutException $e) {
        }
    }
}
