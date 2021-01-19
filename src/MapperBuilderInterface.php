<?php declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Contracts\MapperInterface;

interface MapperBuilderInterface
{
    public function getMapper(): MapperInterface;
    public function end(): ?MapperBuilderInterface;
}
