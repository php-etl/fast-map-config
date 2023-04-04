<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Contract\Mapping\MapperInterface;

interface MapperBuilderInterface
{
    public function getMapper(): MapperInterface;

    public function end(): ?self;
}
