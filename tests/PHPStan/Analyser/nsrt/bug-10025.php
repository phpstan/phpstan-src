<?php declare(strict_types=1);

namespace Bug10025;

use function PHPStan\Testing\assertType;

class MyClass
{
	public int $groupId;
}

/**
 * @param list<MyClass> $foos
 * @param list<MyClass> $bars
 */
function x(array $foos, array $bars): void
{
	$arr = [];
	foreach ($foos as $foo) {
		$arr[$foo->groupId]['foo'][] = $foo;
	}
	foreach ($bars as $bar) {
		$arr[$bar->groupId]['bar'][] = $bar;
	}

	assertType('array<int, non-empty-array{foo?: non-empty-list<Bug10025\MyClass>, bar?: non-empty-list<Bug10025\MyClass>}>', $arr);
	foreach ($arr as $groupId => $group) {
		if (isset($group['foo'])) {
		}
		if (isset($group['bar'])) {
		}
	}

	assertType('array<int, non-empty-array{foo?: non-empty-list<Bug10025\MyClass>, bar?: non-empty-list<Bug10025\MyClass>}>', $arr);
}

