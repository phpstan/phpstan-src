<?php

namespace Bug11322;

use function PHPStan\Testing\assertType;

function doFoo() {
	$result = ['map' => ['a' => 'b']];
	assertType("array{map: array{a: 'b'}}", $result);
	usort($result['map'], fn (string $a, string $b) => $a <=> $b);
	assertType("array{map: non-empty-list<'b'>}", $result);
}
