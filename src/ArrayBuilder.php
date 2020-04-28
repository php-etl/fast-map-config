<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Config;

use Kiboko\Component\ETL\FastMap\Contracts\MapperInterface;
use Kiboko\Component\ETL\FastMap\Mapping\Composite\ArrayMapper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ArrayBuilder implements CompositedMapperBuilderInterface
{
    /** @var MapperBuilderInterface */
    private $parent;
    /** @var ExpressionLanguage */
    private $interpreter;
    /** @var CompositeBuilder */
    private $composition;

    public function __construct(?MapperBuilderInterface $parent = null, ?ExpressionLanguage $interpreter = null)
    {
        $this->parent = $parent;
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
        $this->composition = new CompositeBuilder($this, $this->interpreter);
    }

    public function children(): CompositeBuilder
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

    public function getMapper(): MapperInterface
    {
        return new ArrayMapper(...$this->composition);
    }
}