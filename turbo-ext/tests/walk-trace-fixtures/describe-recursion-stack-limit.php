<?php declare(strict_types = 1);

namespace WalkTraceDescribeRecursionStackLimit;

// from tests/PHPStan/Analyser/data/bug-13801.php: describing $this of Castable
// recurses without end (the bound's `static` is Castable<TCast of Cast<static>>
// again); the twin throws the engine's "Maximum call stack size" Error where
// GenericObjectType::describe() enters its array_map() closure, and the native
// recursion must throw it too instead of overflowing the C stack

/**
 * @template TValue of object
 */
interface Cast
{
}

/**
 * @template TCast of Cast<static>
 */
interface Castable
{
}
