<?php

namespace Foo;

final class ObjectSpaghettiMapper implements \Kiboko\Contract\Mapping\CompiledMapperInterface
{
    public function __invoke($input, $output = null)
    {
        $output = (function ($input) {
            $output = new \functional\Kiboko\Component\FastMapConfig\Customer($input["name"])
        })($output);
        return $output;
        return $output;
    }
}