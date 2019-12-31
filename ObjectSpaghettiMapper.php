<?php

namespace Foo;

final class ObjectSpaghettiMapper implements \Kiboko\Component\ETL\FastMap\Contracts\CompiledMapperInterface
{
    public function __invoke($input, $output = null)
    {
        return $output;
    }
}