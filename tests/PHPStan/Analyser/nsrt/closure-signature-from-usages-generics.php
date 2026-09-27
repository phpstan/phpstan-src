<?php // lint >= 8.0

namespace ClosureSignatureFromUsagesGenerics;

use function PHPStan\Testing\assertType;

/** @template T */
class Collection
{

	/** @param array<T> $items */
	public function __construct(public array $items = [])
	{
	}

	/** @return T */
	public function first()
	{
		return $this->items[0];
	}

	/** @return array<T> */
	public function all(): array
	{
		return $this->items;
	}

	/**
	 * @template U
	 * @param callable(T): U $cb
	 * @return Collection<U>
	 */
	public function map(callable $cb): Collection
	{
		return new Collection();
	}

}

/** @template T */
class Box
{

	/** @param T $value */
	public function __construct(public $value)
	{
	}

}

/** @param Collection<int> $c */
function takesInts(Collection $c): void
{
}

/** @param Box<int> $b */
function takesIntBox(Box $b): void
{
}

class Foo
{

	public function sentToMethodOfWidenedObject(): void
	{
		$c = function ($x) {
			assertType('int', $x);
			return (string) $x;
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		assertType('ClosureSignatureFromUsagesGenerics\Collection<string>', $col->map($c));
	}

	public function invokedWithValueReadOutOfWidenedObject(): void
	{
		$c = function ($x) {
			assertType('int', $x);
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		$c($col->first());
	}

	public function invokedWithValueReadOutOfWidenedObjectThroughVariable(): void
	{
		$c = function ($x) {
			assertType('int', $x);
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		$first = $col->first();
		$c($first);
	}

	public function factsOfStatementsNotReadingWidenedObject(): void
	{
		$col = new Collection([1, 2]);
		takesInts($col);
		$c = function ($x) {
			assertType("'s'|int", $x);
		};
		$unrelated = 'unrelated';
		$c('s');
		$c($col->first());
	}

	public function mappedOverPropertyOfWidenedObject(): void
	{
		$c = fn ($x) => assertType('int', $x);
		$col = new Collection([1, 2]);
		takesInts($col);
		array_map($c, $col->items);
	}

	public function mappedOverMethodOfWidenedObject(): void
	{
		$c = function ($x) {
			assertType('int', $x);
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		array_map($c, $col->all());
	}

	public function invokedWithWidenedObject(): void
	{
		$c = function ($b) {
			assertType('ClosureSignatureFromUsagesGenerics\Box<int>', $b);
		};
		$box = new Box(1);
		$c($box);
		takesIntBox($box);
	}

	public function closureCreatesGenericObject(): void
	{
		$make = function ($v) {
			assertType("1|'a'", $v);
			return new Box($v);
		};
		assertType('ClosureSignatureFromUsagesGenerics\Box<1>', $make(1));
		assertType("ClosureSignatureFromUsagesGenerics\Box<'a'>", $make('a'));
	}

	public function closureFeedsGenericObject(): void
	{
		$col = new Collection();
		$push = function ($x) use ($col) {
			assertType('5', $x);
		};
		$push(5);
		takesInts($col);
		assertType('ClosureSignatureFromUsagesGenerics\Collection<int>', $col);
	}

}
