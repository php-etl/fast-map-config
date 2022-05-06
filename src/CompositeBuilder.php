<?php declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap;
use Kiboko\Contract\Mapping;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyPath;

final class CompositeBuilder implements \IteratorAggregate, CompositeBuilderInterface
{
    private ExpressionLanguage $interpreter;
    /** @var Mapping\FieldScopingInterface[] */
    private array $fields;

    public function __construct(private ?MapperBuilderInterface $parent = null, ?ExpressionLanguage $interpreter = null)
    {
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
        $this->fields = [];
    }

    public function end(): ?MapperBuilderInterface
    {
        if ($this->parent === null) {
            throw new \BadMethodCallException('Could not find parent object, aborting.');
        }
        return $this->parent;
    }

    public function merge(CompositeBuilderInterface ...$builders): self
    {
        foreach ($builders as $builder) {
            array_push($this->fields, ...$builder);
        }

        return $this;
    }

    public function field(string $outputPath, Mapping\FieldMapperInterface $mapper): self
    {
        $this->fields[] = function () use ($outputPath, $mapper) {
            return new FastMap\Mapping\Field(
                new PropertyPath($outputPath),
                $mapper
            );
        };

        return $this;
    }

    public function getMapper(): Mapping\MapperInterface
    {
        return $this->parent->getMapper();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator(
            array_map(function (callable $callback) {
                return $callback([], []);
            }, $this->fields)
        );
    }

    public function copy(string $outputPath, string $inputPath): self
    {
        $this->fields[] = function () use ($outputPath, $inputPath) {
            return new FastMap\Mapping\Field(
                new PropertyPath($outputPath),
                new FastMap\Mapping\Field\CopyValueMapper(
                    new PropertyPath($inputPath)
                )
            );
        };

        return $this;
    }

    public function constant(string $outputPath, $value): self
    {
        $this->fields[] = function () use ($outputPath, $value) {
            return new FastMap\Mapping\Field(
                new PropertyPath($outputPath),
                new FastMap\Mapping\Field\ConstantValueMapper($value)
            );
        };

        return $this;
    }

    public function expression(string $outputPath, string|Expression $expression, array $additionalVariables = []): self
    {
        $this->fields[] = function () use ($outputPath, $expression, $additionalVariables) {
            return new FastMap\Mapping\Field(
                new PropertyPath($outputPath),
                new FastMap\Mapping\Field\ExpressionLanguageValueMapper(
                    $this->interpreter,
                    $expression instanceof Expression ? $expression : new Expression($expression),
                    $additionalVariables,
                )
            );
        };

        return $this;
    }

    public function list(string $outputPath, string|Expression $expression): ArrayBuilderInterface
    {
        $child = new ArrayBuilder($this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new FastMap\Mapping\ListField(
                new PropertyPath($outputPath),
                $this->interpreter,
                $expression instanceof Expression ? $expression : new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }

    public function map(string $outputPath): ArrayBuilderInterface
    {
        $child = new ArrayBuilder($this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath) {
            return new FastMap\Mapping\Field(
                new PropertyPath($outputPath),
                $child->getMapper()
            );
        };

        return $child;
    }

    public function object(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface
    {
        $child = new ObjectBuilder($className, $this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new FastMap\Mapping\SingleRelation(
                new PropertyPath($outputPath),
                $this->interpreter,
                $expression instanceof Expression ? $expression : new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }

    public function collection(string $outputPath, string $className, string|Expression $expression): ObjectBuilderInterface
    {
        $child = new ObjectBuilder($className, $this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new FastMap\Mapping\MultipleRelation(
                new PropertyPath($outputPath),
                $this->interpreter,
                $expression instanceof Expression ? $expression : new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }
}
