<?php

namespace Bug15595;

use function PHPStan\Testing\assertType;

/** @template T */
class Builder
{
    /**
     * @return self<string>
     * @phpstan-impure
     */
    public static function create(): self
    {
        return new self();
    }

    /** @phpstan-self-out self<int> */
    public function select(): void
    {
    }
}

Builder::create()->select();
assertType('Bug15595\\Builder<string>', Builder::create());
