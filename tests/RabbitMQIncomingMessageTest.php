<?php

namespace Suiiz\RabbitMQ\Test;

use Suiiz\RabbitMQ\RabbitMQQueue;
use PhpAmqpLib\Message\AMQPMessage;
use Suiiz\RabbitMQ\RabbitMQDelivery;
use Suiiz\RabbitMQ\RabbitMQExchange;
use Suiiz\RabbitMQ\RabbitMQException;
use Suiiz\RabbitMQ\RabbitMQIncomingMessage;

class RabbitMQIncomingMessageTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIsDefined()
    {
        $this->assertInstanceOf(RabbitMQIncomingMessage::class, new RabbitMQIncomingMessage());
    }

    public function testGettersAndSettersWork()
    {
        $message = new RabbitMQIncomingMessage();
        // Setters
        $message
            ->setStream('test')
            ->setConsumer(null)
            ->setConfig(['key' => 'value'])
            ->setAmqpMessage(new AMQPMessage('test'))
            ->setQueue(new RabbitMQQueue('test'))
            ->setDelivery(new RabbitMQDelivery(['key' => 'value']))
            ->setExchange(new RabbitMQExchange('test'));

        // Getters
        $this->assertEquals('test', $message->getStream());
        $this->assertEquals(null, $message->getConsumer());
        $this->assertEquals('test', $message->getAmqpMessage()->body);
        $this->assertEquals('test', $message->getQueue()->getName());
        $this->assertEquals('value', $message->getDelivery()->getConfig()->get('key'));
        $this->assertEquals('test', $message->getExchange()->getName());
        $this->assertEquals('value', $message->getConfig()->get('key'));
        $this->assertEquals('value', $message->getMessageApplicationHeader('key', 'value'));

        $message->getDelivery()->getConfig()->put('delivery_info', ['redelivered' => true]);
        $this->assertTrue($message->isRedelivered());

        $message->getDelivery()->getConfig()->put('delivery_info', ['']);
        $this->assertFalse($message->isRedelivered());

        $message->setDelivery(null);
        $this->expectException(RabbitMQException::class);
        $message->isRedelivered();
    }
}
