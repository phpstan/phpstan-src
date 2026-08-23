<?php declare(strict_types = 1);

namespace Bug13312NoPollute;

use function PHPStan\Testing\assertType;

/** @param list<mixed> $arr */
function foo(array $arr): void {
	assertType('list<mixed>', $arr);
	foreach ($arr as $v) {
		assertType('non-empty-list<mixed>', $arr);
	}
	assertType('list<mixed>', $arr);

	for ($i = 0; $i < count($arr); ++$i) {
		assertType('non-empty-list<mixed>', $arr);
	}
	assertType('list<mixed>', $arr);
}

/** @param array<string, int> $arr */
function fooStringKeyed(array $arr): void {
	assertType('array<string, int>', $arr);
	foreach ($arr as $v) {
		assertType('non-empty-array<string, int>', $arr);
	}
	assertType('array<string, int>', $arr);
}

/** @param list<mixed> $arr */
function fooReassign(array $arr): void {
	foreach ($arr as $v) {
		$arr = [];
		assertType('array{}', $arr);
	}
	assertType('array{}', $arr);
}
