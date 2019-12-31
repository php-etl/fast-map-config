<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Config;

use Kiboko\Component\ETL\FastMap\Contracts\FieldScopingInterface;
use Kiboko\Component\ETL\FastMap\Contracts\MapperInterface;
use Kiboko\Component\ETL\FastMap\Mapping\Field;
use Kiboko\Component\ETL\FastMap\Mapping\ListField;
use Kiboko\Component\ETL\FastMap\Mapping\MultipleRelation;
use Kiboko\Component\ETL\FastMap\Mapping\SingleRelation;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyPath;

final class CompositeBuilder implements MapperBuilderInterface, \IteratorAggregate
{
    /** @var MapperBuilderInterface */
    private $parent;
    /** @var ExpressionLanguage */
    private $interpreter;
    /** @var FieldScopingInterface[] */
    private $fields;

    public function __construct(?MapperBuilderInterface $parent = null, ?ExpressionLanguage $interpreter = null)
    {
        $this->parent = $parent;
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

    public function merge(CompositeBuilder ...$builders): CompositeBuilder
    {
        foreach ($builders as $builder) {
            array_push($this->fields, ...$builder);
        }

        return $this;
    }

    public function getMapper(): MapperInterface
    {
        return $this->parent->getMapper();
    }

    public function getIterator()
    {
        return new \ArrayIterator(
            array_map(function(callable $callback) {
                return $callback();
            }, $this->fields)
        );
    }

    public function copy(string $outputPath, string $inputPath): CompositeBuilder
    {
        $this->fields[] = function () use ($outputPath, $inputPath) {
            return new Field(
                new PropertyPath($outputPath),
                new Field\CopyValueMapper(
                    new PropertyPath($inputPath)
                )
            );
        };

        return $this;
    }

    public function constant(string $outputPath, $value): CompositeBuilder
    {
        $this->fields[] = function () use ($outputPath, $value) {
            return new Field(
                new PropertyPath($outputPath),
                new Field\ConstantValueMapper($value)
            );
        };

        return $this;
    }

    public function expression(string $outputPath, string $expression): CompositeBuilder
    {
        $this->fields[] = function () use ($outputPath, $expression) {
            return new Field(
                new PropertyPath($outputPath),
                new Field\ExpressionLanguageValueMapper(
                    $this->interpreter,
                    new Expression($expression)
                )
            );
        };

        return $this;
    }

    public function list(string $outputPath, string $expression): ArrayBuilder
    {
        $child = new ArrayBuilder($this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new ListField(
                new PropertyPath($outputPath),
                $this->interpreter,
                new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }

    public function object(string $outputPath, string $expression, string $className): ObjectBuilder
    {
        $child = new ObjectBuilder($className, $this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new SingleRelation(
                new PropertyPath($outputPath),
                $this->interpreter,
                new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }

    public function collection(string $outputPath, string $expression, string $className): ObjectBuilder
    {
        $child = new ObjectBuilder($className, $this, $this->interpreter);

        $this->fields[] = function () use ($child, $outputPath, $expression) {
            return new MultipleRelation(
                new PropertyPath($outputPath),
                $this->interpreter,
                new Expression($expression),
                $child->getMapper()
            );
        };

        return $child;
    }
}