<?php

namespace Suiiz\RabbitMQ;

use Illuminate\Support\Collection;

class RabbitMQQueue
{
    protected string $name;

    protected Collection $config;

    public function __construct(string $name, array $config = [])
    {
        $this->setName($name);
        $this->setConfig($config);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  string  $name
     * @return \Suiiz\RabbitMQ\RabbitMQQueue
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * @param  array  $config
     * @return \Suiiz\RabbitMQ\RabbitMQQueue
     */
    public function setConfig(array $config): self
    {
        $this->config = new Collection($config);

        return $this;
    }
}
