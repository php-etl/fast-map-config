<?php declare(strict_types=1);

namespace functional\Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Compiler;
use Kiboko\Component\FastMap\PropertyAccess\EmptyPropertyPath;
use Kiboko\Component\FastMapConfig\ArrayBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ArrayBuilderTest extends TestCase
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
    public function testThatArrayCompiles($input)
    {
        $interpreter = new ExpressionLanguage();
        $interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));
        $interpreter->addFunction(
            new ExpressionFunction(
            'price',
                function (string $value, string $currency)
                {
                    return sprintf('sprintf("%%s %%s", number_format(%s, 2), %s)', $value, $currency);
                },
                function (float $value, string $currency)
                {
                    return sprintf('%s %s', number_format($value, 2), $currency);
                }
            )
        );

        $mapper = (new ArrayBuilder(null, $interpreter))
            ->children()
            ->constant('[type]', 'ORDER')
            ->copy('[customer][first_name]', '[customer][firstName]')
            ->copy('[customer][last_name]', '[customer][lastName]')
            ->list('[items]', 'merge( input["items"], input["shippings"] )')
                ->children()
                ->copy('[sku]', '[sku]')
                ->expression('[price]', 'price( input["price"]["value"], input["price"]["currency"] )')
                ->end()
            ->end()
            ->end()
            ->getMapper();

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\ArraySpaghettiMapper'
            ),
            $mapper
        );

        $this->assertEquals(
            [
                "type" => "ORDER",
                "items" => [
                    ["sku" => "123456", "price" => '123.45 EUR'],
                    ["sku" => "234567", "price" => '23.45 EUR'],
                    ["sku" => "123456", "price" => '123.45 EUR'],
                    ["sku" => "234567", "price" => '23.45 EUR']
                ],
                "customer" => [
                    "first_name" => "John",
                    "last_name" => "Doe"
                ]
            ],
            $result($input)
        );
    }

    /**
     * @dataProvider validConfigProvider
     */
    public function testThatArrayCompilesWithExpression($input)
    {
        $interpreter = new ExpressionLanguage();
        $interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));
        $interpreter->addFunction(
            new ExpressionFunction(
                'price',
                function (string $value, string $currency)
                {
                    return sprintf('sprintf("%%s %%s", number_format(%s, 2), %s)', $value, $currency);
                },
                function (float $value, string $currency)
                {
                    return sprintf('%s %s', number_format($value, 2), $currency);
                }
            )
        );

        $mapper = (new ArrayBuilder(null, $interpreter))
            ->children()
                ->constant('[type]', 'ORDER')
                ->copy('[customer][first_name]', '[customer][firstName]')
                ->copy('[customer][last_name]', '[customer][lastName]')
                ->list('[items]', 'merge( input["items"], input["shippings"] )')
                    ->children()
                        ->copy('[sku]', '[sku]')
                        ->expression('[price]', new Expression('price( input["price"]["value"], input["price"]["currency"] )'))
                    ->end()
                ->end()
            ->end()
            ->getMapper();

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\ArraySpaghettiMapper'
            ),
            $mapper
        );

        $this->assertEquals(
            [
                "type" => "ORDER",
                "items" => [
                    ["sku" => "123456", "price" => '123.45 EUR'],
                    ["sku" => "234567", "price" => '23.45 EUR'],
                    ["sku" => "123456", "price" => '123.45 EUR'],
                    ["sku" => "234567", "price" => '23.45 EUR']
                ],
                "customer" => [
                    "first_name" => "John",
                    "last_name" => "Doe"
                ]
            ],
            $result($input)
        );
    }

    /**
     * @dataprovider validConfigProvider
     */
    public function testFailIfNoParent()
    {
        $this->expectExceptionMessage('Could not find parent object, aborting.');

        $interpreter = new ExpressionLanguage();
        $mapper = (new ArrayBuilder(null, $interpreter));

        $mapper->end();
    }
}
