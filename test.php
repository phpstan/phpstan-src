<?php

use function PHPStan\Testing\assertType;

function fooBar(mixed $mixed): void {
    assertType('mixed', $mixed);
    foreach ($mixed as $v) {
        assertType('non-empty-array|Traversable', $mixed);
    }
    assertType('mixed', $mixed);
}
