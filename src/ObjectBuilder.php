<?php declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap;
use Kiboko\Component\Metadata;
use Kiboko\Contract\Mapping;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ObjectBuilder implements Mapping\ObjectBuilderInterface
{
    private CompositeBuilder $composition;
    /** @var Mapping\FieldScopingInterface[] */
    private array $arguments;

    public function __construct(
        private string $className,
        private ?Mapping\MapperBuilderInterface $parent = null,
        private ?ExpressionLanguage $interpreter = null
    ) {
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
        $this->composition = new CompositeBuilder($this, $this->interpreter);
        $this->arguments = [];
    }

    public function children(): Mapping\CompositeBuilderInterface
    {
        return $this->composition;
    }

    public function end(): ?Mapping\MapperBuilderInterface
    {
        if ($this->parent === null) {
            throw new \BadMethodCallException('Could not find parent object, aborting.');
        }
        return $this->parent;
    }

    public function arguments(string ...$expressions): Mapping\ObjectBuilderInterface
    {
        $this->arguments = array_map(function ($expression) {
            return new Expression($expression);
        }, $expressions);

        return $this;
    }

    public function getMapper(): FastMap\Mapping\Composite\ObjectMapper
    {
        return new FastMap\Mapping\Composite\ObjectMapper(
            new FastMap\SimpleObjectInitializer(
                new Metadata\ClassReferenceMetadata($this->getClassName(), $this->getNamespace()),
                $this->interpreter,
                ...$this->arguments
            ),
            ...$this->composition
        );
    }

    private function getClassName(): string
    {
        if ($pos = strrpos($this->className, '\\')) {
            return substr($this->className, $pos + 1);
        }

        return $this->className;
    }

    private function getNamespace(): string
    {
        if ($pos = strrpos($this->className, '\\')) {
            return substr($this->className, 0, -strlen($this->getClassName()) - 1);
        }

        return $this->className;
    }
}
