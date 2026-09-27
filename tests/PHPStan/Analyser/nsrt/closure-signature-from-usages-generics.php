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
		assertType('Closure(int): decimal-int-string', $c);

		$f = fn ($x) => (string) $x;
		$arrowCol = new Collection([1, 2]);
		takesInts($arrowCol);
		assertType('ClosureSignatureFromUsagesGenerics\Collection<string>', $arrowCol->map($f));
		assertType('Closure(int): decimal-int-string', $f);
	}

	public function invokedWithValueReadOutOfWidenedObject(): void
	{
		$c = function ($x) {
			assertType('int', $x);
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		$c($col->first());
		assertType('Closure(int): void', $c);

		$f = fn ($x) => assertType('int', $x);
		$arrowCol = new Collection([1, 2]);
		takesInts($arrowCol);
		$f($arrowCol->first());
		assertType('Closure(int): mixed', $f);
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
		assertType('Closure(int): void', $c);
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
		assertType("Closure('s'|int): void", $c);
	}

	public function mappedOverPropertyOfWidenedObject(): void
	{
		$c = fn ($x) => assertType('int', $x);
		$col = new Collection([1, 2]);
		takesInts($col);
		array_map($c, $col->items);
		assertType('Closure(int): mixed', $c);
	}

	public function mappedOverMethodOfWidenedObject(): void
	{
		$c = function ($x) {
			assertType('int', $x);
		};
		$col = new Collection([1, 2]);
		takesInts($col);
		array_map($c, $col->all());
		assertType('Closure(int): void', $c);
	}

	public function invokedWithWidenedObject(): void
	{
		$c = function ($b) {
			assertType('ClosureSignatureFromUsagesGenerics\Box<int>', $b);
		};
		$box = new Box(1);
		$c($box);
		takesIntBox($box);
		assertType('Closure(ClosureSignatureFromUsagesGenerics\Box<int>): void', $c);

		$f = fn ($b) => assertType('ClosureSignatureFromUsagesGenerics\Box<int>', $b);
		$arrowBox = new Box(1);
		$f($arrowBox);
		takesIntBox($arrowBox);
		assertType('Closure(ClosureSignatureFromUsagesGenerics\Box<int>): mixed', $f);
	}

	public function closureCreatesGenericObject(): void
	{
		$make = function ($v) {
			assertType("1|'a'", $v);
			return new Box($v);
		};
		assertType('ClosureSignatureFromUsagesGenerics\Box<1>', $make(1));
		assertType("ClosureSignatureFromUsagesGenerics\Box<'a'>", $make('a'));
		assertType("Closure(1|'a'): ClosureSignatureFromUsagesGenerics\Box<1|'a'>", $make);
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
		assertType('Closure(5): void', $push);
	}

}
