<?php

namespace Foo;

final class ObjectSpaghettiMapper implements \Kiboko\Component\FastMap\Contracts\CompiledMapperInterface
{
    public function __invoke($input, $output = null)
    {
        return $output;
    }
}
