<?php declare(strict_types=1);

namespace functional\Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Compiler;
use Kiboko\Component\FastMap\PropertyAccess\EmptyPropertyPath;
use Kiboko\Component\FastMapConfig\ObjectBuilder;
use PHPUnit\Framework\TestCase;

final class ObjectBuilderTest extends TestCase
{
    public function testThatBuilderHasCorrectArguments ()
    {
        $builder = (new ObjectBuilder(
            'functional\Kiboko\Component\FastMapConfig\Customer',
        ))->arguments('input["name"]')->getMapper();

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\ObjectSpaghettiMapper'
            ),
            $builder
        );

        /** @var Customer $customer */
        $customer = $result([
            'name' => 'Jean'
        ]);

        $this->assertInstanceOf(
            Customer::class,
            $customer
        );

        $this->assertEquals(
            'Jean',
            $customer->getName()
        );
    }
}
