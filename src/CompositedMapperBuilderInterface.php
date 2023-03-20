<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

interface CompositedMapperBuilderInterface extends MapperBuilderInterface
{
    public function children(): CompositeBuilderInterface;
}
