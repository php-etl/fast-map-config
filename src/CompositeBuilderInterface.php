<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Contract\Mapping\FieldMapperInterface;
use Symfony\Component\ExpressionLanguage\Expression;

interface CompositeBuilderInterface extends MapperBuilderInterface
{
    public function merge(self ...$builders): self;

    public function field(string $outputPath, FieldMapperInterface $mapper): self;

    public function copy(string $outputPath, string $inputPath): self;

    public function constant(string $outputPath, $value): self;

    public function expression(string $outputPath, string|Expression $expression, array $additionalVariables = []): self;

    public function list(string $outputPath, string|Expression $expression): ArrayBuilderInterface;

    public function map(string $outputPath, string|Expression $expression): ArrayBuilderInterface;

    public function object(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface;

    public function collection(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface;
}
