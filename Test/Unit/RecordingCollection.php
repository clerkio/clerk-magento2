<?php

namespace Clerk\Clerk\Test\Unit;

/**
 * Records collection calls and stays chainable, like a Magento collection.
 */
class RecordingCollection
{
    /**
     * @var array<int, array{0: string, 1: array}>
     */
    public array $calls = [];

    public function __call(string $name, array $args): self
    {
        $this->calls[] = [$name, $args];
        return $this;
    }
}
