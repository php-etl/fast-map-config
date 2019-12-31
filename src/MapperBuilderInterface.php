<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Config;

use Kiboko\Component\ETL\FastMap\Contracts\MapperInterface;

interface MapperBuilderInterface
{
    public function getMapper(): MapperInterface;
    public function end(): ?MapperBuilderInterface;
}