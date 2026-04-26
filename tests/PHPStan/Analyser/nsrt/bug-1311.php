<?php declare(strict_types = 1);

namespace Bug1311;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param array<int, array{a: string}> $sets
	 *
	 * @return array<int, array{a: string, b: bool}>
	 */
	public function sayHello(array $sets): array
	{
		foreach ($sets as &$set) {
			$set['b'] = false;
		}

		assertType('array<int, array{a: string, b: false}>', $sets);

		return $sets;
	}
}

function foreachByRefConstantArray(): void
{
	$temp = [1, 2, 3];

	foreach ($temp as &$item) {
		$item = (string) $item;
	}

	assertType("array{'1', '2', '3'}", $temp);
}

function foreachByRefConstantArrayWithKey(): void
{
	$temp = [1, 2, 3];

	foreach ($temp as $key => &$item) {
		$item = (string) $item;
	}

	assertType("array{'1', '2', '3'}", $temp);
}

function foreachByRefShapedArray(): void
{
	/** @var array{a: int, b: int} $data */
	$data = ['a' => 1, 'b' => 2];

	foreach ($data as &$val) {
		$val = (string) $val;
	}

	assertType("array{a: lowercase-string&numeric-string&uppercase-string, b: lowercase-string&numeric-string&uppercase-string}", $data);
}

function foreachByRefListPreservation(): void
{
	/** @var list<int> $list */
	$list = [1, 2, 3];

	foreach ($list as &$item) {
		$item = $item * 2;
	}

	assertType("list<int>", $list);
}

function foreachByRefConditionalModification(): void
{
	$temp = [1, 2, 3];

	foreach ($temp as &$item) {
		if ($item > 1) {
			$item = (string) $item;
		}
	}

	assertType("array{1, '2', '3'}", $temp);
}

function foreachByRefAppend(): void
{
	$data = ['a' => 1, 'b' => 2];

	foreach ($data as &$val) {
		$val = [$val, 'extra'];
	}

	assertType("array{a: array{1, 'extra'}, b: array{2, 'extra'}}", $data);
}

function forLoop(): void
{
	$temp = [1, 2, 3];

	for ($i = 0; $i < count($temp); $i++) {
		$temp[$i] = (string) $temp[$i];
	}

	assertType("array{1|(literal-string&lowercase-string&non-falsy-string&numeric-string&uppercase-string), 2|(literal-string&lowercase-string&non-falsy-string&numeric-string&uppercase-string), 3|(literal-string&lowercase-string&non-falsy-string&numeric-string&uppercase-string)}", $temp);
}
