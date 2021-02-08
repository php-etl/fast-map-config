<?php

namespace Kiboko\Component\FastMapConfig;

interface ObjectBuilderInterface extends CompositedMapperBuilderInterface
{
    public function arguments(string ...$expressions): ObjectBuilderInterface;
}
