<?php

namespace Suiiz\RabbitMQ\Test;

use Suiiz\RabbitMQ\ConsumeConfig;
use Suiiz\RabbitMQ\ConnectionConfig;

class ConsumeConfigTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIsDefined()
    {
        $this->assertInstanceOf(ConsumeConfig::class, new ConsumeConfig());
    }

    public function testItStoresConfig()
    {
        $config = new ConsumeConfig(['key' => 'value']);
        $this->assertEquals('value', $config->get('key'));
    }

    public function testItReturnsConnectionConfig()
    {
        $config = new ConsumeConfig([], new ConnectionConfig(['key' => 'value']));
        $this->assertEquals('value', $config->getConnectionConfig()->get('key'));
    }
}
