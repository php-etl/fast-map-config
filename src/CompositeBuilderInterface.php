<?php

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Contract\Mapping\FieldMapperInterface;
use Symfony\Component\ExpressionLanguage\Expression;

interface CompositeBuilderInterface extends MapperBuilderInterface
{
    public function merge(CompositeBuilderInterface ...$builders): CompositeBuilderInterface;

    public function field(string $outputPath, FieldMapperInterface $mapper): CompositeBuilderInterface;

    public function copy(string $outputPath, string $inputPath): CompositeBuilderInterface;

    public function constant(string $outputPath, $value): CompositeBuilderInterface;

    public function expression(string $outputPath, string|Expression $expression, array $additionalVariables = []): CompositeBuilderInterface;

    public function list(string $outputPath, string|Expression $expression): ArrayBuilderInterface;

    public function map(string $outputPath, string|Expression $expression): ArrayBuilderInterface;

    public function object(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface;

    public function collection(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface;
}
