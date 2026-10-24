<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15414;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @template TValue
 * @implements \IteratorAggregate<int, TValue>
 */
final class Coll implements \IteratorAggregate
{
	/** @param list<TValue> $items */
	public function __construct(private array $items = [])
	{
	}

	/** @return \ArrayIterator<int, TValue> */
	#[\Override]
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->items);
	}

	/**
	 * @template TMergeValue
	 * @param iterable<TMergeValue> $items
	 * @return Coll<TValue|TMergeValue>
	 */
	public function merge(iterable $items): Coll
	{
		return new Coll();
	}

	/**
	 * @param (callable(TValue): bool)|TValue $callback
	 * @return Coll<TValue>
	 */
	public function reject($callback): Coll
	{
		return $this;
	}

	/**
	 * @param (Closure(TValue): bool)|TValue $callback
	 * @return Coll<TValue>
	 */
	public function rejectClosure($callback): Coll
	{
		return $this;
	}
}

final class Field
{
	public bool $hidden = false;

	public function isHidden(Field $field): bool
	{
		return $field->hidden;
	}

	public static function isHiddenStatic(Field $field): bool
	{
		return $field->hidden;
	}
}

final class FieldFilter
{
	public function __invoke(Field $field): bool
	{
		return $field->hidden;
	}
}

function isExcluded(Field $field): bool
{
	return $field->hidden;
}

/**
 * @param Coll<Field> $a
 * @param Coll<Field> $b
 */
function test(Coll $a, Coll $b, Field $f, FieldFilter $filter): void
{
	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject(isExcluded(...)));
	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject(static fn (Field $f): bool => isExcluded($f)));
	assertType('Bug15414\Coll<Bug15414\Field>', $a->reject(isExcluded(...)));

	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject($f->isHidden(...)));
	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject(Field::isHiddenStatic(...)));
	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject(static function (Field $f): bool {
		return isExcluded($f);
	}));

	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->rejectClosure(isExcluded(...)));
	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->rejectClosure(static fn (Field $f): bool => isExcluded($f)));

	assertType('Bug15414\Coll<Bug15414\Field>', $a->merge($b)->reject($filter));
}

function testEmpty(Field $f): void
{
	$c = new Coll();
	$c = $c->merge([$f])->reject(isExcluded(...));
	assertType('Bug15414\Coll<Bug15414\Field>', $c);
}
