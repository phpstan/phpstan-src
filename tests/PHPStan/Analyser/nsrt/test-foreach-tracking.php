<?php

namespace TestForeachTracking;

use function PHPStan\Testing\assertType;

class Base {}
class Derived extends Base {}

/** @param list<Base> $items */
function testSimple(array $items): void
{
    foreach ($items as $item) {
        // At this point, $item should be tracked as coming from $items
        assertType('TestForeachTracking\Base', $item);

        if ($item instanceof Derived) {
            // This should narrow both $item AND $items
            assertType('TestForeachTracking\Derived', $item);
            assertType('non-empty-list<TestForeachTracking\Derived>', $items);
        }

        assertType('TestForeachTracking\Base', $item);
    }
}

/** @param array<int, Base> $items */
function testDirectAccess(array $items): void
{
    if ($items[0] instanceof Derived) {
        // This should narrow the array
        // Note: Direct access uses hasOffsetValue metadata rather than full type narrowing
        assertType('non-empty-array<int, TestForeachTracking\Base>&hasOffsetValue(0, TestForeachTracking\Derived)', $items);
    }
}
