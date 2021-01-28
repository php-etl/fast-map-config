<?php declare(strict_types=1);

namespace functional\Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Compiler;
use Kiboko\Component\FastMap\PropertyAccess\EmptyPropertyPath;
use Kiboko\Component\FastMapConfig\ArrayBuilder;
use Kiboko\Component\FastMapConfig\CompositeBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class CompositeBuilderTest extends TestCase
{
    public function validConfigProvider(): \Generator
    {
        yield [
            'input' => [
                'customer' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'joh@example.com',
                ],
                'items' => [
                    [
                        'sku' => '123456',
                        'price' => [
                            'value' => 123.45,
                            'currency' => 'EUR',
                        ],
                        'weight' => [
                            'value' => 123.45,
                            'KILOGRAM',
                        ],
                    ],
                    [
                        'sku' => '234567',
                        'price' => [
                            'value' => 23.45,
                            'currency' => 'EUR',
                        ],
                        'weight' => [
                            'value' => 13.45,
                            'KILOGRAM',
                        ],
                    ],
                ],
                'shippings' => [
                    [
                        'sku' => '123456',
                        'price' => [
                            'value' => 123.45,
                            'currency' => 'EUR',
                        ],
                    ],
                    [
                        'sku' => '234567',
                        'price' => [
                            'value' => 23.45,
                            'currency' => 'EUR',
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testThatCompositeBuilderCompiles($input)
    {
        $interpreter = new ExpressionLanguage();
        $interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));

        $mapper = (new CompositeBuilder(new ArrayBuilder(), $interpreter))
            ->constant('[type]', 'ORDER')
            ->copy('[customer][first_name]', '[customer][firstName]')
            ->copy('[customer][last_name]', '[customer][lastName]')
            ->list('[items]', 'merge( input["items"], input["shippings"] )')
            ->children()
            ->copy('[sku]', '[sku]')
            ->end()
            ->end()
            ->getMapper();

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\CompositeSpaghettiMapper'
            ),
            $mapper
        );

        $this->assertEquals(
            [],
            $result($input)
        );
    }

    public function testFailIfNoParent()
    {
        $this->expectExceptionMessage('Could not find parent object, aborting.');

        $interpreter = new ExpressionLanguage();
        $mapper = (new CompositeBuilder(null, $interpreter));

        $mapper->end();
    }

    public function testFailedToMerge()
    {
        $interpreter = new ExpressionLanguage();
        $mapper = (new CompositeBuilder(null, $interpreter));

        $mapper->merge(new CompositeBuilder(null, $interpreter));

        $this->assertEmpty($mapper->getIterator());
    }
}
