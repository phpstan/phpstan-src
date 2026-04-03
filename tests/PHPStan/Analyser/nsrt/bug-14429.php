<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14429Nsrt;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;

/**
 * @param \ArrayObject<string, int> $intKeyMap
 * @param \ArrayObject<string, string> $stringMap
 */
function testBoth(\ArrayObject $intKeyMap, \ArrayObject $stringMap): void
{
    foreach ($intKeyMap as $intKeyMapValue) {
        assertType("int", $intKeyMapValue);
        assertNativeType("mixed", $intKeyMapValue);
    }
    foreach ($stringMap as $stringMapValue) {
        assertType("string", $stringMapValue);
        assertNativeType("mixed", $stringMapValue);
    }
}
