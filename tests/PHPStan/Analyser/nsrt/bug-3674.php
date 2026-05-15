<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug3674;

use ArrayIterator;
use Generator;
use Iterator;
use function PHPStan\Testing\assertType;

/** @return Generator<int, string> */
function gen(): Generator { yield 'hello'; }

function testGeneratorCurrent(): void
{
	$it = gen();
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
}

function testGeneratorAfterValid(): void
{
	$it = gen();
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

function testGeneratorInForeach(): void
{
	foreach (gen() as $key => $value) {
		assertType('string', $value);
		assertType('int', $key);
	}
}

/** @param Iterator<int, string> $it */
function testIteratorCurrent(Iterator $it): void
{
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
}

/** @param Iterator<int, string> $it */
function testIteratorAfterValid(Iterator $it): void
{
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

/** @param Iterator<int, string> $it */
function testIteratorInForeach(Iterator $it): void
{
	foreach ($it as $key => $value) {
		assertType('string', $value);
		assertType('int', $key);
	}
}

/** @param ArrayIterator<int, string> $it */
function testArrayIteratorCurrent(ArrayIterator $it): void
{
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
}

/** @param ArrayIterator<int, string> $it */
function testArrayIteratorAfterValid(ArrayIterator $it): void
{
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

function testGeneratorWhileLoop(): void
{
	$it = gen();
	$it->rewind();
	while ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
		$it->next();
	}
}

function testGeneratorSend(): void
{
	/** @var Generator<int, string, int, void> $gen */
	$gen = gen();
	assertType('string|null', $gen->send(42));
}

/** @return Generator<mixed, int> */
function genInt(): Generator { yield 1; }

function testOriginalIssue(): void
{
	$it = genInt();
	assertType('int|null', $it->current());
}

/** @param Iterator<int, string> $it */
function testNegatedValid(Iterator $it): void
{
	if (!$it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

function testWhileLoopWithValid(): void
{
	$it = gen();
	while ($it->valid()) {
		$v = $it->current();
		assertType('string|null', $v);
		$k = $it->key();
		assertType('int|null', $k);
		$it->next();
	}
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
}

/**
 * @template T
 * @implements Iterator<int, T>
 */
class CustomIterator implements Iterator
{
	/** @return T|null */
	public function current(): mixed { return null; }
	public function key(): int { return 0; }
	public function next(): void {}
	public function rewind(): void {}
	public function valid(): bool { return false; }
}

/** @param CustomIterator<string> $it */
function testCustomIterator(CustomIterator $it): void
{
	assertType('string|null', $it->current());
	assertType('int', $it->key());
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int', $it->key());
	}
}

/** @param \IteratorIterator<int, string, Iterator<int, string>> $it */
function testIteratorIterator(\IteratorIterator $it): void
{
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

/** @param \NoRewindIterator<int, string, Iterator<int, string>> $it */
function testNoRewindIterator(\NoRewindIterator $it): void
{
	assertType('string|null', $it->current());
	assertType('int|null', $it->key());
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

/** @param Iterator<int, string> $it */
function testNextResetsNarrowing(Iterator $it): void
{
	if ($it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
		$it->next();
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

/** @param Iterator<int, string> $it */
function testRewindResetsNarrowing(Iterator $it): void
{
	if (!$it->valid()) {
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
		$it->rewind();
		assertType('string|null', $it->current());
		assertType('int|null', $it->key());
	}
}

/**
 * @implements Iterator<int, string>
 */
class NonNullIterator implements Iterator
{
	public function current(): string { return 'hello'; }
	public function key(): int { return 0; }
	public function next(): void {}
	public function rewind(): void {}
	public function valid(): bool { return false; }
}

function testNonNullOverride(NonNullIterator $it): void
{
	assertType('string', $it->current());
	assertType('int', $it->key());
}
