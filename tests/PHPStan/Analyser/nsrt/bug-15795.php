<?php // lint >= 8.1

namespace Bug15795Nsrt;

use RuntimeException;
use function PHPStan\Testing\assertType;

interface A {}
interface B {}

/**
 * @template TIntersection of (A&B)
 * @template TUnion of (A|B)
 * @template TObject of object
 */
final class Batch
{
	/**
	 * @param class-string<TIntersection> $intersection
	 * @param class-string<TUnion> $union
	 * @param class-string<TObject> $object
	 */
	public function __construct(
		private readonly string $intersection,
		private readonly string $union,
		private readonly string $object,
	) {}

	/** @param list<A&B> $items */
	public function intersection(array $items): void
	{
		foreach ($items as $item) {
			if (!$item instanceof $this->intersection) {
				throw new RuntimeException();
			}
			$x = assertType('TIntersection of Bug15795Nsrt\A&Bug15795Nsrt\B (class Bug15795Nsrt\Batch, argument)', $item);
		}
	}

	/** @param list<A|B> $items */
	public function union(array $items): void
	{
		foreach ($items as $item) {
			if (!$item instanceof $this->union) {
				throw new RuntimeException();
			}
			$x = assertType('TUnion of Bug15795Nsrt\A|Bug15795Nsrt\B (class Bug15795Nsrt\Batch, argument)', $item);
		}
	}

	/** @param list<object> $items */
	public function object(array $items): void
	{
		foreach ($items as $item) {
			if (!$item instanceof $this->object) {
				throw new RuntimeException();
			}
			$x = assertType('TObject of object (class Bug15795Nsrt\Batch, argument)', $item);
		}
	}
}
