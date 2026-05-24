<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14661Static;

class A
{
    public static function mixedOrder(
        ?string $other = null,
        ?string $target = null,
    ): void {}
}

class B
{
    public static function mixedOrder(
        ?string $target = null,
        ?string $other = null,
    ): void {}
}

/**
 * @param class-string<A>|class-string<B> $class
 */
function staticMixedOrder(string $class): void
{
    $class::mixedOrder(target: 'value');
    $class::mixedOrder(other: 'a', target: 'b');
    $class::mixedOrder(target: 'b', other: 'a');
}
