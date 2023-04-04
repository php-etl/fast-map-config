<?php

declare(strict_types=1);

namespace functional\Kiboko\Component\FastMapConfig;

final class Customer
{
    public function __construct(
        public string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
