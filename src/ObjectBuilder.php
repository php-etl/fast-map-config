<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Mapping\Composite\ObjectMapper;
use Kiboko\Component\FastMap\SimpleObjectInitializer;
use Kiboko\Component\Metadata;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ObjectBuilder implements ObjectBuilderInterface
{
    private readonly CompositeBuilder $composition;
    /** @var array<int|string, Expression> */
    private array $arguments = [];

    public function __construct(
        private readonly string $className,
        private readonly ?MapperBuilderInterface $parent = null,
        private readonly ExpressionLanguage $interpreter = new ExpressionLanguage()
    ) {
        $this->composition = new CompositeBuilder($this, $this->interpreter);
    }

    public function children(): CompositeBuilderInterface
    {
        return $this->composition;
    }

    public function end(): ?MapperBuilderInterface
    {
        if (null === $this->parent) {
            throw new \BadMethodCallException('Could not find parent object, aborting.');
        }

        return $this->parent;
    }

    public function arguments(string ...$expressions): ObjectBuilderInterface
    {
        $this->arguments = array_map(fn ($expression) => new Expression($expression), $expressions);

        return $this;
    }

    public function getMapper(): ObjectMapper
    {
        return new ObjectMapper(
            new SimpleObjectInitializer(
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
            return substr($this->className, 0, -\strlen($this->getClassName()) - 1);
        }

        return $this->className;
    }
}
