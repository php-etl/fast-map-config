<?php declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Mapping\Composite\ObjectMapper;
use Kiboko\Component\FastMap\SimpleObjectInitializer;
use Kiboko\Component\Metadata\ClassReferenceMetadata;
use Kiboko\Contract\Mapping\CompositeBuilderInterface;
use Kiboko\Contract\Mapping\FieldScopingInterface;
use Kiboko\Contract\Mapping\MapperBuilderInterface;
use Kiboko\Contract\Mapping\ObjectBuilderInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ObjectBuilder implements ObjectBuilderInterface
{
    private CompositeBuilder $composition;
    /** @var FieldScopingInterface[] */
    private array $arguments;

    public function __construct(
        private string $className,
        private ?MapperBuilderInterface $parent = null,
        private ?ExpressionLanguage $interpreter = null
    ) {
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
        $this->composition = new CompositeBuilder($this, $this->interpreter);
        $this->arguments = [];
    }

    public function children(): CompositeBuilderInterface
    {
        return $this->composition;
    }

    public function end(): ?MapperBuilderInterface
    {
        if ($this->parent === null) {
            throw new \BadMethodCallException('Could not find parent object, aborting.');
        }
        return $this->parent;
    }

    public function arguments(string ...$expressions): ObjectBuilderInterface
    {
        $this->arguments = array_map(function ($expression) {
            return new Expression($expression);
        }, $expressions);

        return $this;
    }

    public function getMapper(): ObjectMapper
    {
        return new ObjectMapper(
            new SimpleObjectInitializer(
                new ClassReferenceMetadata($this->getClassName(), $this->getNamespace()),
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
