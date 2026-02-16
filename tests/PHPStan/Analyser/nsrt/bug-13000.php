<?php declare(strict_types = 1);

namespace Bug13000;

use function PHPStan\Testing\assertType;

function (): void {
	$r = [];
	foreach (['a' => '1', 'b' => '2'] as $key => $val) {
		$r[$key] = $val;
	}
	assertType("array{a?: '1'|'2', b?: '1'|'2'}", $r); // could be array{a: '1', b: '2'}
};
