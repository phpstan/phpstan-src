<?php declare(strict_types = 1);

namespace Bug15080;

use function array_key_last;
use function array_search;
use function count;
use function PHPStan\Testing\assertType;
use function sizeof;
use const COUNT_RECURSIVE;

/**
 * @param list<int> $list
 * @return list<int>
 */
function appendToList(array $list, int $value): array {
	$list[count($list)] = $value;
	assertType('non-empty-list<int>', $list);
	return $list;
}

/** @return list<int> */
function foo(): array { return []; }

function appendWithSizeof(): void {
	$list = foo();
	$list[sizeof($list)] = 37;
	assertType('non-empty-list<int>', $list);
}

function appendToKnownSizeList(): void {
	$list = foo();
	if (count($list) === 3) {
		assertType('array{int, int, int}', $list);
		$list[count($list)] = 37;
		assertType('array{int, int, int, 37}', $list);
	}
}

function appendToConstantArray(): void {
	$list = [1, 2, 3];
	$list[count($list)] = 37;
	assertType('array{1, 2, 3, 37}', $list);
}

function writeBehindConstantArray(): void {
	$list = [1, 2, 3];
	$list[count($list) + 1] = 37;
	assertType('array{0: 1, 1: 2, 2: 3, 4: 37}', $list);
}

function appendInLoop(): void {
	$list = foo();
	for ($i = 0; $i < 10; $i++) {
		$list[count($list)] = $i;
	}
	assertType('non-empty-list<int>', $list);
}

function appendWithCoalesceAssign(): void {
	$list = foo();
	$list[count($list)] ??= 37;
	assertType('non-empty-list<int>', $list);
}

function appendWithCountMinusZero(): void {
	$list = foo();
	$list[count($list) - 0] = 37;
	assertType('non-empty-list<int>', $list);
}

function appendAfterKeyLast(): void {
	$list = foo();
	if (count($list) > 0) {
		$list[array_key_last($list) + 1] = 37;
		assertType('non-empty-list<int>', $list);
		$list[1 + array_key_last($list)] = 38;
		assertType('non-empty-list<int>', $list);
	}
}

function appendAfterKeyLastOfPossiblyEmptyList(): void {
	$list = foo();
	// array_key_last() returns null on an empty list, so null + 1 leaves a hole
	$list[array_key_last($list) + 1] = 37;
	assertType('non-empty-array<int<0, max>, int>', $list);
}

function countOfDifferentArray(array $other): void {
	$list = foo();
	$list[count($other)] = 37;
	assertType('non-empty-array<int<0, max>, int>', $list);
}

function countRecursive(): void {
	$list = foo();
	$list[count($list, COUNT_RECURSIVE)] = 37;
	assertType('non-empty-array<int<0, max>, int>', $list);
}

function unpackedCountArgs(): void {
	$list = foo();
	$list[count(...$list)] = 37;
	assertType('non-empty-array<int<0, max>, int>', $list);
}

function arraySearchWithSingleArg(): void {
	$list = foo();
	// no crash even though array_search() is called with too few arguments
	$list[array_search($list)] = 37;
	assertType('non-empty-array<int|string, int>', $list);
}

function nestedList(): void {
	/** @var array{x: list<int>} $data */
	$data = ['x' => []];
	$data['x'][count($data['x'])] = 37;
	assertType('non-empty-list<int>', $data['x']);
}

function nestedListWithVariableKey(string $key): void {
	/** @var array<string, list<int>> $data */
	$data = [];
	$data[$key][count($data[$key])] = 37;
	assertType('non-empty-list<int>', $data[$key]);
}

class HelloWorld
{

	/** @var list<int> */
	public array $list = [];

	/** @var list<int> */
	public static array $staticList = [];

	/** @var array{x: list<int>} */
	public array $nested = ['x' => []];

	public function appendToProperty(): void
	{
		$this->list[count($this->list)] = 37;
		assertType('non-empty-list<int>', $this->list);
	}

	public function appendToStaticProperty(): void
	{
		self::$staticList[count(self::$staticList)] = 37;
		assertType('non-empty-list<int>', self::$staticList);
	}

	public function appendToNestedProperty(): void
	{
		$this->nested['x'][count($this->nested['x'])] = 37;
		assertType('non-empty-list<int>', $this->nested['x']);
	}

	public function overwriteKeyLastOfProperty(): void
	{
		if (count($this->list) > 0) {
			$this->list[array_key_last($this->list)] = 37;
			assertType('non-empty-list<int>', $this->list);
		}
	}

	public function overwriteCountMinusOneOfProperty(): void
	{
		if (count($this->list) > 0) {
			$this->list[count($this->list) - 1] = 37;
			assertType('non-empty-list<int>', $this->list);
		}
	}

	public function overwriteArraySearchOfProperty(int $needle): void
	{
		$this->list[array_search($needle, $this->list)] = 37;
		assertType('non-empty-list<int>', $this->list);
	}

	public function countOfDifferentArray(array $other): void
	{
		$this->list[count($other)] = 37;
		assertType('non-empty-array<int<0, max>, int>', $this->list);
	}

}
