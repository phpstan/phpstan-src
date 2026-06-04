<?php declare(strict_types = 1);

namespace Bug14525;

use function PHPStan\Testing\assertType;

function basicArrayWalk(): void
{
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value, $key): void {
		$value = (string) $value;
	});
	assertType("array{a: '1'|'2', b: '1'|'2'}", $array);
}

function arrayWalkGeneric(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value, $key): void {
		$value = (string) $value;
	});
	assertType('array<string, decimal-int-string>', $array);
}

function arrayWalkNoModification(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value, $key): void {
		echo $value;
	});
	assertType("array<string, int>", $array);
}

function arrayWalkConditionalModification(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value, string $key): void {
		if ($key === 'a') {
			$value = 'modified';
			return;
		}
	});
	assertType("array<string, 'modified'|int>", $array);
}

function arrayWalkWithoutByRef(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function ($value, $key): void {
		$value = (string) $value;
	});
	assertType("array<string, int>", $array);
}

function arrayWalkNonEmptyArray(): void
{
	/** @var non-empty-array<string, int> $array */
	$array = ['a' => 1];
	array_walk($array, function (&$value): void {
		$value = (string) $value;
	});
	assertType('non-empty-array<string, decimal-int-string>', $array);
}

function arrayWalkList(): void
{
	/** @var list<int> $list */
	$list = [1, 2, 3];
	array_walk($list, function (&$value): void {
		$value = (string) $value;
	});
	assertType('list<decimal-int-string>', $list);
}

function arrayWalkAlwaysTerminating(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value): void {
		$value = (string) $value;
		return;
	});
	assertType('array<string, decimal-int-string>', $array);
}

function arrayWalkNestedArray(): void
{
	$array = ['a' => ['x' => 1, 'y' => 2], 'b' => ['z' => 3]];
	array_walk($array, function (&$value): void {
		$value = count($value);
	});
	assertType("array{a: 1|2, b: 1|2}", $array);
}

function arrayWalkWithNestedClosure(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value): void {
		$inner = array_map(function ($x) {
			return $x * 2;
		}, [1, 2, 3]);
		$value = (string) $value;
	});
	assertType('array<string, decimal-int-string>', $array);
}

function arrayWalkWithNestedClosureByRef(): void
{
	/** @var array<string, int> $array */
	$array = ['a' => 1, 'b' => 2];
	array_walk($array, function (&$value): void {
		$capture = null;
		$fn = function () use (&$capture): void {
			$capture = 'hello';
		};
		$fn();
		$value = (string) $value;
	});
	assertType('array<string, decimal-int-string>', $array);
}
