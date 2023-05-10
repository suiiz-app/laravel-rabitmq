# Laravel RabbitMQ

The RabbitMQ Laravel Package is a fork of the original laravel-rabbitmq package by kunalvarma05. It provides seamless integration with RabbitMQ messaging queue system in Laravel applications. It allows you to efficiently exchange messages between different parts of your application, facilitating asynchronous and distributed processing.

By leveraging the power of RabbitMQ, you can improve the performance and scalability of your Laravel application. With this package, you can easily configure exchanges, queues, and bindings, and publish/consume messages using familiar Laravel syntax and conventions.

## Features
- Producers
- Consumers
- Publish / Subscribe
- Exchanges
  - Default
  - Direct
  - Topic
  - Fanout

## Requirements

- PHP 8.1+
- Laravel 10.0+

## Setup

### 1. Installation
Add the repository URL to your project's `composer.json` file:

   ```json
   "repositories": [
      {
        "type": "vcs",
        "url": "git@github.com:suiiz-app/laravel-rabitmq.git"
      }
    ]
```
In the same composer.json file, under the "require" section, add the package and specify the desired version:
  ```json
    "require": {
        "suiiz-app/laravel-rabitmq": "dev-main"
    }
```
### 2. Default Configuration

```sh
php artisan vendor:publish --tag=rabbitmq-config
```

Open config/app.php file and add the following line to the providers:
```php
Suiiz\RabbitMQ\RabbitMQServiceProvider::class,
```
## Quick Start

### **Initialize**

There are multiple ways of initializing the library:

```php
// A. Direct instantiation
$rabbitMQ = new RabbitMQManager(app());

// B. Binding
$rabbitMQ = app('rabbitmq');

// C. Dependency injection (Controller, Command, Job, etc.)
public function __consturct(RabbitMQManager $rabbitMQ) { ... }

// D. Facade
// All the public methods of the `RabbitMQManager` class
// are available through the `RabbitMQ` facade.
RabbitMQ::getConnections();
```

### **Publish**

```php
$message = new RabbitMQMessage('message body');

// Publish to the default exchange/topic/queue
$rabbitMQ->publisher()->publish($message);

// Publish bulk messages
$messages = [new RabbitMQMessage('message 1'), new RabbitMQMessage('message 2')];
$rabbitMQ->publisher()->publish($messages);
```

### **Consume**

```php
// A. Consume through a closure
$handler = new RabbitMQGenericMessageConsume(function (RabbitMQIncomingMessage $message) {
  $content = $message->getStream();
});

// B. Consume through a class
class MyMessageConsumer extends RabbitMQMessageConsumer {
  public function handle(RabbitMQIncomingMessage $message) {
    $content = $message->getStream();
  }
}
$handler = new MyMessageConsumer();

// Starts a blocking loop `while (true)`
$rabbitMQ->consumer()->consume($handler);
```

### **Interact**

```php
// Resolve the default connection
// @see: AMQPSSLConnection https://github.com/php-amqplib/php-amqplib/blob/master/PhpAmqpLib/Connection/AMQPSSLConnection.php
$amqpConnection = $rabbitMQ->resolveConnection();

// Resolve the default channel
// @see: AMQPChannel https://github.com/php-amqplib/php-amqplib/blob/master/PhpAmqpLib/Channel/AMQPChannel.php
$amqpChannel = $rabbitMQ->resolveChannel();
```

### **Configuration**

#### Connection Configuration

```php
$connectionName = 'custom_connection'; // Set to `null` for default connection
// Override the default connection config
$connectionConfig = new ConnectionConfig(['username' => 'quest', 'password' => 'quest']);
$connectionConfig->setHost('localhost');
$customConnection = $rabbitMQ->resolveConnection($connectionName, $connectionConfig);
```

#### Message Configuration

```php
$config = [
  'content_encoding' => 'UTF-8',
  'content_type'     => 'text/plain',
  'delivery_mode'    => AMQPMessage::DELIVERY_MODE_PERSISTENT,
];
$message = new RabbitMQMessage('message body', $config);

// Set message exchange
$exchangeConfig = ['type' => AMQPExchangeType::DIRECT];
$exchange = new RabbitMQExchange('my_exchange', $exchangeConfig);
$message->setExchange($exchange);
```

#### Publish Configuration

```php
$publisher = $rabbitMQ->publisher();

$message = new RabbitMQMessage('message body');

$exchangeConfig = ['type' => AMQPExchangeType::TOPIC];
$exchange = new RabbitMQExchange('my_exchange', $exchangeConfig);

$message->setExchange($exchange);

$routingKey = 'key'; // Can be an empty string, but not null
$connectionName = 'custom_connection'; // Set to null for default connection

// The publish config allows you to any override default configuration
//
// The following precendence works for the configuration:
// Message exchange config > Publish config > Connection config > Default config
//
// In this case, the exchange type used would be AMQPExchangeType::TOPIC
$publishConfig = new PublishConfig(['exchange' => ['type' => AMQPExchangeType::FANOUT]]);

$publisher->publish($message, $routingKey, $connectionName, $publishConfig);
```

#### Consumer Configuration

```php
$consumer = $rabbitMQ->consumer();
$routingKey = 'key';

$exchange = new RabbitMQExchange('test_exchange', ['declare' => true, 'durable' => true]);
$queue = new RabbitMQQueue('my_queue', ['declare' => true, 'durable' => true]);

$messageConsumer = new RabbitMQGenericMessageConsumer(
    function (RabbitMQIncomingMessage $message) {
      // Acknowledge a message
      $message->getDelivery()->acknowledge();
      // Reject a message
      $requeue = true; // Reject and Requeue
      $message->getDelivery()->reject($requeue);
    },
    $this,
);

// A1. Set the exchange and the queue directly
$messageConsumer
    ->setExchange($exchange)
    ->setQueue($queue);

// OR

// A2. Set the exchange and the queue through config
$consumeConfig = new ConsumeConfig(
  [
    'queue' => [
      'name' => 'my_queue',
      'declare' => true,
      'durable' => true,
  ],
  'exchange' => [
      'name' => 'test_exchange',
      'declare' => true,
    ],
  ],
);

$consumer->consume(
    $messageConsumer,
    $routingKey,
    null,
    $consumeConfig,
);
```

## **Example**

### Running a Consumer

- Create a custom command:

```sh
php artisan make:command MyRabbitConsumer --command "rabbitmq:my-consumer {--queue=} {--exchange=} {--routingKey=}"
```

- Register the command in `app/Console/Kernel.php`

```php
protected $commands = [
  MyRabbitConsumer::class,
];
```

- Consume through the handler

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Suiiz\RabbitMQ\RabbitMQQueue;
use Suiiz\RabbitMQ\RabbitMQExchange;
use Suiiz\RabbitMQ\RabbitMQIncomingMessage;
use Suiiz\RabbitMQ\RabbitMQGenericMessageConsumer;

class MyRabbitConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:my-consumer {--queue} {--exchange} {--routingKey}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'My consumer command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $rabbitMQ = app('rabbitmq');
        $messageConsumer = new RabbitMQGenericMessageConsumer(
            function (RabbitMQIncomingMessage $message) {
                // Handle message
                $this->info($message->getStream()); // Print to console
            },
            $this, // Scope the closure to the command
        );

        $routingKey = $this->option('routingKey') ?? '';
        $queue = new RabbitMQQueue($this->option('queue') ?? '', ['declare' => true]);
        $exchange = new RabbitMQExchange($this->option('exchange') ?? '', ['declare' => true]);

        $messageConsumer
            ->setExchange($exchange)
            ->setQueue($queue);

        $rabbitMQ->consumer()->consume($messageConsumer, $routingKey);
    }
}
```

- Call the command from the console

```sh
php artisan rabbitmq:my-consumer --queue='my_queue' --exchange='test_exchange' --routingKey='key'
```

### Publishing Messages

- Create route <-> controller binding

```php
Route::get('/publish', 'MyRabbitMQController@publish');
```

- Create a controller to publish message

```php
class MyRabbitMQController extends Controller {

  public function publish(Request $request)
  {
    $rabbitMQ = app('rabbitmq');
    $consumer = $rabbitMQ->consumer();
    $routingKey = 'key'; // The key used by the consumer

    // The exchange (name) used by the consumer
    $exchange = new RabbitMQExchange('test_exchange', ['declare' => true]);

    $contents = $request->get('message', 'random message');

    $message = new RabbitMQMessage($contents);
    $message->setExchange($exchange);

    $rabbitMQ->publisher()->publish(
        $message,
        $routingKey
    );

    return ['message' => "Published {$contents}"];
  }
}
```

- Make a request or browse to: `http://localhost:8000/publish?message=Hello`

- Check your console for the message `Hello` to be printed

## Tests

```php
composer run-script test
```
