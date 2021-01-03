<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Config;

interface CompositedMapperBuilderInterface extends MapperBuilderInterface
{
    public function children(): CompositeBuilderInterface;
}