<?php

declare(strict_types=1);

namespace functional\Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Compiler;
use Kiboko\Component\FastMap\PropertyAccess\EmptyPropertyPath;
use Kiboko\Component\FastMapConfig\ArrayBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
/**
 * @internal
 *
 * @coversNothing
 */
final class ArrayBuilderTest extends TestCase
{
    public static function validConfigProvider(): \Generator
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
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validConfigProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function thatArrayCompiles(mixed $input): void
    {
        $interpreter = new ExpressionLanguage();
        $interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));
        $interpreter->addFunction(
            new ExpressionFunction(
                'price',
                fn (string $value, string $currency) => sprintf('sprintf("%%s %%s", number_format(%s, 2), %s)', $value, $currency),
                fn (float $value, string $currency) => sprintf('%s %s', number_format($value, 2), $currency)
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
            ->getMapper()
        ;

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\ArraySpaghettiMapper'
            ),
            $mapper
        );

        $this->assertEquals(
            [
                'type' => 'ORDER',
                'items' => [
                    ['sku' => '123456', 'price' => '123.45 EUR'],
                    ['sku' => '234567', 'price' => '23.45 EUR'],
                    ['sku' => '123456', 'price' => '123.45 EUR'],
                    ['sku' => '234567', 'price' => '23.45 EUR'],
                ],
                'customer' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
            $result($input)
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('validConfigProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function thatArrayCompilesWithExpression(mixed $input): void
    {
        $interpreter = new ExpressionLanguage();
        $interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));
        $interpreter->addFunction(
            new ExpressionFunction(
                'price',
                fn (string $value, string $currency) => sprintf('sprintf("%%s %%s", number_format(%s, 2), %s)', $value, $currency),
                fn (float $value, string $currency) => sprintf('%s %s', number_format($value, 2), $currency)
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
            ->getMapper()
        ;

        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        $result = $compiler->compile(
            Compiler\StandardCompilationContext::build(
                new EmptyPropertyPath(), __DIR__, 'Foo\\ArraySpaghettiMapper'
            ),
            $mapper
        );

        $this->assertEquals(
            [
                'type' => 'ORDER',
                'items' => [
                    ['sku' => '123456', 'price' => '123.45 EUR'],
                    ['sku' => '234567', 'price' => '23.45 EUR'],
                    ['sku' => '123456', 'price' => '123.45 EUR'],
                    ['sku' => '234567', 'price' => '23.45 EUR'],
                ],
                'customer' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
            $result($input)
        );
    }

    /**
     * @dataprovider validConfigProvider
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function failIfNoParent(): void
    {
        $this->expectExceptionMessage('Could not find parent object, aborting.');

        $interpreter = new ExpressionLanguage();
        $mapper = (new ArrayBuilder(null, $interpreter));

        $mapper->end();
    }
}
