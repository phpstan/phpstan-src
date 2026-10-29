<?php

namespace Bug15595New;

use function PHPStan\Testing\assertType;

/** @template T */
class Builder
{
    /** @phpstan-self-out self<int> */
    public function select(): void
    {
    }
}

assertType('Bug15595New\\Builder<mixed>', new Builder());
(new Builder())->select();
assertType('Bug15595New\\Builder<mixed>', new Builder());

$builder = new Builder();
$builder->select();
assertType('Bug15595New\\Builder<int>', $builder);
