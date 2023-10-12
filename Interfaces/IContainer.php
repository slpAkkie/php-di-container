<?php

namespace Uwi\Container\Interfaces;

use Psr\Container\ContainerInterface;

interface IContainer extends ContainerInterface
{
    public function set(string $id, mixed $value): void;
}
