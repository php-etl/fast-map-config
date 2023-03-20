<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

interface ObjectBuilderInterface extends CompositedMapperBuilderInterface
{
    public function arguments(string ...$expressions): self;
}
