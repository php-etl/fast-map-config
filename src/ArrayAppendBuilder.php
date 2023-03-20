<?php

declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap\Mapping\Composite\ArrayAppendMapper;
use Kiboko\Contract\Mapping\ArrayMapperInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final readonly class ArrayAppendBuilder implements ArrayBuilderInterface
{
    private CompositeBuilder $composition;

    public function __construct(private ?MapperBuilderInterface $parent = null, private ExpressionLanguage $interpreter = new ExpressionLanguage())
    {
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

    public function getMapper(): ArrayMapperInterface
    {
        return new ArrayAppendMapper(...$this->composition);
    }
}
