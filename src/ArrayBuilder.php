<?php declare(strict_types=1);

namespace Kiboko\Component\FastMapConfig;

use Kiboko\Component\FastMap;
use Kiboko\Contract\Mapping;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ArrayBuilder implements Mapping\ArrayBuilderInterface
{
    private ExpressionLanguage $interpreter;
    private CompositeBuilder $composition;

    public function __construct(private ?Mapping\MapperBuilderInterface $parent = null, ?ExpressionLanguage $interpreter = null)
    {
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
        $this->composition = new CompositeBuilder($this, $this->interpreter);
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

    public function getMapper(): Mapping\ArrayMapperInterface
    {
        return new FastMap\Mapping\Composite\ArrayMapper(...$this->composition);
    }
}
