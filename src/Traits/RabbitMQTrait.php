<?php

namespace Suiiz\RabbitMQ\Traits;

use Suiiz\RabbitMQ\RabbitMQExchange;
use Suiiz\RabbitMQ\RabbitMQMessage;

trait RabbitMQTrait
{
    /**
     * Mapping of exchange types to their corresponding PhpAmqpLib constants.
     *
     * @var array
     */
    protected $exchange_types = [
        'direct' => \PhpAmqpLib\Exchange\AMQPExchangeType::DIRECT,
        'fanout' => \PhpAmqpLib\Exchange\AMQPExchangeType::FANOUTT,
        'topic' => \PhpAmqpLib\Exchange\AMQPExchangeType::TOPIC,
        'headers' => \PhpAmqpLib\Exchange\AMQPExchangeType::HEADERS
    ];

    /**
     * Publishes a message to a RabbitMQ exchange.
     *
     * @param string $exchange_name The name of the exchange to publish the message to.
     * @param string $routingKey The routing key for the message.
     * @param array $contents The contents of the message.
     * @param string $exchange_type The type of the exchange (default: 'fanout').
     * @param bool $passive Whether to perform a passive declaration of the exchange (default: false).
     * @param bool $durable Whether the exchange should survive a broker restart (default: true).
     * @param bool $auto_delete Whether the exchange should be deleted when it has no more queues (default: false).
     * @param bool $internal Whether the exchange is internal, i.e., cannot be directly published to by clients (default: false).
     * @param bool $nowait Whether to wait for a confirmation from the broker after publishing (default: true).
     * @param array $properties Additional properties to set for the exchange (default: []).
     * @param bool $message_presistent Whether the message should be persisted (default: true).
     * @return void
     */
    public function publishMessage(string $exchange_name, string $routingKey, array $contents, string $exchange_type = 'topic', bool $passive = false, bool $durable = true, bool $auto_delete = false, bool $internal = false, bool $nowait = true, array $properties = [], bool $message_presistent = true): void
    {
        // Get the RabbitMQ instance
        $rabbitMQ = app('rabbitmq');

        // Configure the exchange
        $exchangeConfig = [
            'declare' => true,
            'type' => $this->exchange_types[$exchange_type],
            'passive' => $passive,
            'durable' => $durable,
            'auto_delete' => $auto_delete,
            'internal' => $internal,
            'nowait' => $nowait,
            'properties' => $properties,
        ];

        // Create the RabbitMQ exchange
        $exchange = new RabbitMQExchange($exchange_name, $exchangeConfig);

        // Configure the message
        $config = [
            'content_encoding' => 'UTF-8',
            'content_type' => 'application/json',
            'delivery_mode' => $message_presistent ?\PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_PERSISTENT : \PhpAmqpLib\Message\AMQPMessage::DELIVERY_MODE_NON_PERSISTENT,
        ];

        // Create the RabbitMQ message
        $message = new RabbitMQMessage(json_encode($contents), $config);
        $message->setExchange($exchange);

        // Publish the message to the exchange
        $rabbitMQ->publisher()->publish(
            $message,
            $routingKey
        );
    }


}
