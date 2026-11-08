<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15406b;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
final class Box
{
	/** @param T $value */
	public function __construct(public mixed $value)
	{
	}

	/**
	 * @template U
	 * @param callable(T): U $callback
	 * @return Box<U>
	 */
	public function map(callable $callback): Box
	{
		return new Box($callback($this->value));
	}
}

/**
 * @template T
 * @param callable(): T $callback
 * @return T
 */
function run(callable $callback): mixed
{
	return $callback();
}

function threeLevels(): void
{
	$box = new Box(1);
	assertType('Bug15406b\\Box<Bug15406b\\Box<Bug15406b\\Box<array{k: 1}>>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): Box => (new Box($j))->map(fn (int $k): array => ['k' => $k]))));
}

function twoLevels(): void
{
	/** @var Box<int> $box */
	$box = new Box(1);
	assertType('Bug15406b\\Box<Bug15406b\\Box<array{j: int}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));
}

run(function (): void {
	$box = new Box(1);
	assertType('Bug15406b\\Box<Bug15406b\\Box<array{j: 1}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));

	/** @var Box<int> $box */
	$box = new Box(1);
	assertType('Bug15406b\\Box<Bug15406b\\Box<array{j: int}>>', $box->map(fn (int $i): Box => (new Box($i))->map(fn (int $j): array => ['j' => $j])));
});
