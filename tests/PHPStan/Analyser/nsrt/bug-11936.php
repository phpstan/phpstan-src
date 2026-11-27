<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug11936;

use ArrayObject;
use function PHPStan\Testing\assertType;

/**
 * @template A
 * @template B
 * @template C
 *
 * @param A $items
 * @param callable(A): B $ab
 * @param callable(B): C $bc
 * @return C
 */
function pipe(mixed $items, callable $ab, callable $bc): mixed
{
    return $bc($ab($items));
}

/**
 * @return ArrayObject<string, bool>
 */
function getObject(): ArrayObject
{
    return new ArrayObject;
}

$result = pipe(
	getObject(),
	function(ArrayObject $i) {
        assertType('ArrayObject<string, bool>', $i);
        return $i;
    },
	function(ArrayObject $i) {
        assertType('ArrayObject<string, bool>', $i);
        return $i;
    },
);

assertType('ArrayObject<string, bool>', $result);
