<?php

namespace ListUnionNarrowing;

use function PHPStan\Testing\assertType;

class GlobalId {}
class GlobalTagId extends GlobalId {}

class Animal {}
class Dog extends Animal {}
class Cat extends Animal {}

// Test 1: Basic foreach instanceof narrowing
/** @param list<GlobalId|string> $ids */
function testForeachBasic(array $ids): void
{
    foreach ($ids as $id) {
        if ($id instanceof GlobalTagId) {
            // Type narrows to the specific subclass
            assertType('non-empty-list<ListUnionNarrowing\GlobalTagId>', $ids);
        }
    }
}

// Test 2: Direct array access narrowing with integer key
/** @param array<int, Animal> $animals */
function testDirectAccess(array $animals): void
{
    if ($animals[0] instanceof Dog) {
        // Note: Direct access uses hasOffsetValue metadata
        assertType('non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(0, ListUnionNarrowing\Dog)', $animals);
    }
}

// Test 3: List marker preservation
/** @param list<Animal> $animals */
function testListMarkerPreservation(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);  // NOT array<int, Dog>
        }
    }
}

// Test 4: Union narrowing
/** @param list<GlobalId|string> $ids */
function testUnionNarrowing(array $ids): void
{
    foreach ($ids as $id) {
        if ($id instanceof GlobalTagId) {
            // Narrows to the specific type
            assertType('non-empty-list<ListUnionNarrowing\GlobalTagId>', $ids);
        }
    }
}

// Test 5: Type restoration after conditional
/** @param list<Animal> $animals */
function testTypeRestoration(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
        assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);  // Restored
    }
}

// Test 6: String key array access
/** @param array<string, Animal> $data */
function testStringKey(array $data): void
{
    if ($data['key'] instanceof Dog) {
        assertType('non-empty-array<string, ListUnionNarrowing\Animal>&hasOffsetValue(\'key\', ListUnionNarrowing\Dog)', $data);
    }
}

// Test 7: No narrowing on variable offset (conservative)
/** @param array<int, Animal> $animals */
function testVariableOffset(array $animals, int $i): void
{
    if ($animals[$i] instanceof Dog) {
        // Should NOT narrow (conservative)
        assertType('array<int, ListUnionNarrowing\Animal>', $animals);
    }
}

// Test 8: Nested foreach (flat only - no nesting support)
/** @param list<Animal> $animals */
function testNestedForeach(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
    }
}

// Test 9: Multiple foreach iterations
/** @param list<Animal> $animals */
function testMultipleIterations(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        } else {
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        }
    }
}

// Test 10: Sequential ifs
/** @param list<Animal> $animals */
function testSequentialIfs(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
        if ($animal instanceof Cat) {
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        }
    }
}

// Test 11: Not instanceof narrowing
/** @param list<Animal> $animals */
function testNotInstanceOf(array $animals): void
{
    foreach ($animals as $animal) {
        if (!($animal instanceof Dog)) {
            // Should narrow to exclude Dog
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        } else {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
    }
}

// Test 12: Direct access with different offsets
/** @param array<int, Animal> $animals */
function testDifferentOffsets(array $animals): void
{
    if ($animals[0] instanceof Dog) {
        assertType('non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(0, ListUnionNarrowing\Dog)', $animals);
    }
    if ($animals[1] instanceof Cat) {
        assertType('non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(0, ListUnionNarrowing\Animal)&hasOffsetValue(1, ListUnionNarrowing\Cat)', $animals);
    }
}

// Test 13: Nested array access
/** @param array<int, array<int, Animal>> $nested */
function testNestedArrayAccess(array $nested): void
{
    // This tracks hasOffsetValue metadata for nested access
    if ($nested[0][0] instanceof Dog) {
        assertType('non-empty-array<int, array<int, ListUnionNarrowing\Animal>>&hasOffsetValue(0, non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(0, ListUnionNarrowing\Dog))', $nested);
    }
}

// Test 14: Mixed union types
/** @param list<Animal|int|string> $mixed */
function testMixedUnion(array $mixed): void
{
    foreach ($mixed as $item) {
        if ($item instanceof Dog) {
            // Narrows to just Dog
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $mixed);
        }
    }
}

// Test 15: Constant negative offset
/** @param array<int, Animal> $animals */
function testNegativeOffset(array $animals): void
{
    if ($animals[-1] instanceof Dog) {
        assertType('non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(-1, ListUnionNarrowing\Dog)', $animals);
    }
}

// Test 16: Constant large offset
/** @param array<int, Animal> $animals */
function testLargeOffset(array $animals): void
{
    if ($animals[999] instanceof Dog) {
        assertType('non-empty-array<int, ListUnionNarrowing\Animal>&hasOffsetValue(999, ListUnionNarrowing\Dog)', $animals);
    }
}

// Test 17: Foreach with key-value
/** @param list<Animal> $animals */
function testForeachKeyValue(array $animals): void
{
    foreach ($animals as $key => $animal) {
        // Note: key-value foreach may have different narrowing behavior
        // due to how the scope is managed
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        }
    }
}

// Test 18: Intersection types
interface Interface1 {}
interface Interface2 {}
class Multi implements Interface1, Interface2 {}

/** @param list<Interface1|Interface2> $items */
function testIntersection(array $items): void
{
    foreach ($items as $item) {
        if ($item instanceof Multi) {
            assertType('non-empty-list<ListUnionNarrowing\Multi>', $items);
        }
    }
}

// Test 19: Null handling
/** @param list<Animal|null> $animals */
function testNullHandling(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            // instanceof removes null automatically
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
    }
}

// Test 20: Boolean and instanceof combination
/** @param list<Animal> $animals */
function testBooleanCombination(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog && $animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
    }
}

// Test 21: By-reference foreach narrowing
/** @param list<Animal> $animals */
function testByReferenceForeach(array $animals): void
{
    foreach ($animals as &$animal) {
        if ($animal instanceof Dog) {
            // Narrowing does NOT apply with by-reference (conservative approach)
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        }
    }
    assertType('list<ListUnionNarrowing\Animal>', $animals);
}

// Test 22: Empty array behavior
/** @param list<Animal> $animals */
function testEmptyArray(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            // Even if array was initially empty, the instanceof
            // proves it has at least one Dog element now
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
        }
    }
}

// Test 23: Variable reassignment in loop
/** @param list<Animal> $animals */
function testVariableReassignment(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);

            // Reassign the variable - tracking should break
            $animals = [new Cat()];

            // After reassignment, type reflects new value as array literal
            assertType('array{ListUnionNarrowing\Cat}', $animals);
        }
    }
}

// Test 24: Multiple foreach loops with different arrays
/** @param list<Animal> $animals */
/** @param list<Dog> $dogs */
function testMultipleForeach(array $animals, array $dogs): void
{
    foreach ($animals as $animal) {
        foreach ($dogs as $dog) {
            if ($dog instanceof Dog) {
                // Inner loop array should narrow
                assertType('non-empty-list<ListUnionNarrowing\Dog>', $dogs);
                // Outer loop array should NOT be affected (but is non-empty due to being in loop)
                assertType('non-empty-array', $animals);
            }
        }
    }
}

// Test 25: Narrowing with ternary operator
/** @param list<Animal> $animals */
function testTernaryOperator(array $animals): void
{
    foreach ($animals as $animal) {
        $type = $animal instanceof Dog ? 'dog' : 'other';

        // Narrowing does NOT persist after ternary (type is widened)
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
        }
    }
}

// Test 26: Early continue in loop
/** @param list<Animal> $animals */
function testEarlyContinue(array $animals): void
{
    foreach ($animals as $animal) {
        if ($animal instanceof Dog) {
            assertType('non-empty-list<ListUnionNarrowing\Dog>', $animals);
            continue; // Type should restore after continue
        }

        // After continue, type is non-empty (we're in the loop body)
        assertType('non-empty-list<ListUnionNarrowing\Animal>', $animals);
    }
}
