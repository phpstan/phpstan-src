<?php // lint >= 8.5

namespace Bug15595CloneWith;

use function PHPStan\Testing\assertType;

/** @template T */
class Builder
{
    public int $x = 0;

    /** @phpstan-self-out self<int> */
    public function select(): void
    {
    }
}

/** @param Builder<string> $b */
function cloneWith(Builder $b): void
{
    assertType('Bug15595CloneWith\\Builder<string>', clone($b, []));
    (clone($b, []))->select();
    assertType('Bug15595CloneWith\\Builder<string>', clone($b, []));
    assertType('Bug15595CloneWith\\Builder<string>', $b);

    assertType('Bug15595CloneWith\\Builder<string>', clone($b, ['x' => 1]));
    (clone($b, ['x' => 1]))->select();
    assertType('Bug15595CloneWith\\Builder<string>', clone($b, ['x' => 1]));

    assertType('Bug15595CloneWith\\Builder<string>', \clone($b));
    (\clone($b))->select();
    assertType('Bug15595CloneWith\\Builder<string>', \clone($b));

    $copy = clone($b, []);
    $copy->select();
    assertType('Bug15595CloneWith\\Builder<int>', $copy);
}
