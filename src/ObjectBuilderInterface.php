<?php

namespace Kiboko\Component\ETL\Config;

interface ObjectBuilderInterface extends CompositedMapperBuilderInterface
{
    public function arguments(string ...$expressions): ObjectBuilderInterface;
}