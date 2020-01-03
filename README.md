FastMap Config, a fluent interface for configuring your mappings
===

Example 1: configure mapping for an array
---

```php
<?php

use Kiboko\Component\ETL\Config\ArrayBuilder;
use Kiboko\Component\ETL\FastMap\Compiler;
use Kiboko\Component\ETL\FastMap\PropertyAccess\EmptyPropertyPath;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

$input = [
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
];

$compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

$interpreter = new Symfony\Component\ExpressionLanguage\ExpressionLanguage();
$interpreter->addFunction(ExpressionFunction::fromPhp('array_merge', 'merge'));
$interpreter->addFunction(new ExpressionFunction(
    'price',
    function (array $context, float $value, string $currency) {
        return sprintf('\sprintf("%%s %%s", \number_format(%s, 2), "%s")', $value, addslashes($currency));
    },
    function (array $context, float $value, string $currency) {
        return sprintf('%s %s', number_format($value, 2), $currency);
    }
));

$mapper = (new ArrayBuilder(null, $interpreter))
    ->children()
        ->constant('[type]', 'ORDER')
        ->copy('[customer][first_name]', '[customer][firstName]')
        ->copy('[customer][last_name]', '[customer][lastName]')
        ->list('[products]', 'merge( input["items"], input["shippings"] )')
            ->children()
                ->copy('[sku]', '[sku]')
                ->expression('[price]', 'price( input["price"]["value"], input["price"]["currency"] )')
            ->end()
        ->end()
    ->end()
    ->getMapper();

$compiler->compile(
    Compiler\StandardCompilationContext::build(new EmptyPropertyPath(), __DIR__, 'Foo\\ArraySpaghettiMapper'),
    $mapper
);

var_dump($mapper($input, [], new EmptyPropertyPath()));
```

Example 2: configure mapping for an object
---

```php
<?php

use Kiboko\Component\ETL\Config\CompositeBuilder;use Kiboko\Component\ETL\Config\ObjectBuilder;
use Kiboko\Component\ETL\FastMap\Compiler;
use Kiboko\Component\ETL\FastMap\PropertyAccess\EmptyPropertyPath;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;

class Order
{
    public ?Customer $customer = null;
    public iterable $products;

    public function __construct()
    {
        $this->products = [];
    }
}
class Customer
{
    public string $firstName;
    public string $lastName;
}
class Product
{
    public string $sku;
    public ?Price $price = null;
}
class Price
{
    public float $amount;
    public string $currency;
}

$mapper = (new ObjectBuilder(Order::class, null, $interpreter))
    ->children()
        ->object('customer', 'input["customer"]', Customer::class)
            ->children()
                ->merge(
                    (new CompositeBuilder(null, $interpreter))
                        ->copy('firstName', '[firstName]')
                        ->copy('lastName', '[lastName]')
                )
            ->end()
        ->end()
        ->collection('products', 'merge( input["items"], input["shippings"] )', Product::class)
            ->children()
                ->copy('sku', '[sku]')
                ->object('price', 'input["price"]', Price::class)
                    ->children()
                        ->expression('amount', 'input["value"]')
                        ->expression('currency', 'input["currency"]')
                    ->end()
                ->end()
            ->end()
        ->end()
    ->end()
    ->getMapper();

var_dump($mapper($input, [], new EmptyPropertyPath()));

$compiler->compile(
    Compiler\StandardCompilationContext::build(new EmptyPropertyPath(), __DIR__, 'Foo\\ObjectSpaghettiMapper'),
    $mapper
);
```