<?php

namespace Bug15595Clone;

use function PHPStan\Testing\assertType;

/** @template T */
class Builder
{
    /** @phpstan-self-out self<int> */
    public function select(): void
    {
    }
}

$builder = new Builder();
assertType('Bug15595Clone\\Builder<mixed>', clone $builder);
(clone $builder)->select();
assertType('Bug15595Clone\\Builder<mixed>', clone $builder);
assertType('Bug15595Clone\\Builder<mixed>', $builder);

$copy = clone $builder;
$copy->select();
assertType('Bug15595Clone\\Builder<int>', $copy);
