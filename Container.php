<?php

namespace Uwi\Container;

use Uwi\Container\Interfaces\IContainer;

class Container implements IContainer
{
    private array $storage = array();

    public function set(string $id, mixed $value): void
    {
        $this->storage[$id] = $value;
    }

    public function has(string $id): bool
    {
        return key_exists($id, $this->storage);
    }

    public function get(string $id): mixed
    {
        return $this->has($id) ? $this->storage[$id] : throw new \Exception;
    }
}
