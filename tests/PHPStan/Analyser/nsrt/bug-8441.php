<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug8441;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Collection {
	/**
	 * @param T|null $t
	 */
	public function __construct(private readonly mixed $t = null) {}
}

class Test {
	/** @var Collection<int> */
	private readonly Collection $collection;

	public function __construct() {
		$this->collection = new Collection();
		assertType('Bug8441\Collection<int>', $this->collection);
	}
}

/**
 * @template T
 */
class CollectionWithNonNullableParam {
	/**
	 * @param T $t
	 */
	public function __construct(private readonly mixed $t = null) {}
}

class TestNonNullableParam {
	/** @var CollectionWithNonNullableParam<int> */
	private readonly CollectionWithNonNullableParam $collection;

	public function __construct() {
		$this->collection = new CollectionWithNonNullableParam();
		assertType('Bug8441\CollectionWithNonNullableParam<null>', $this->collection);
	}
}

/**
 * @template T
 * @param T|null $x
 * @return T
 */
function nullableParam($x = null)
{
	if ($x === null) {
		throw new \LogicException();
	}

	return $x;
}

class Service
{

	/**
	 * @template T
	 * @param T|null $x
	 * @return T
	 */
	public function method($x = null)
	{
		if ($x === null) {
			throw new \LogicException();
		}

		return $x;
	}

	/**
	 * @template T
	 * @param T|null $x
	 * @return T
	 */
	public static function staticMethod($x = null)
	{
		if ($x === null) {
			throw new \LogicException();
		}

		return $x;
	}

	/**
	 * @template T
	 * @param T|false $x
	 * @return T
	 */
	public function falseParam($x = false)
	{
		if ($x === false) {
			throw new \LogicException();
		}

		return $x;
	}

	/**
	 * @template T
	 * @param T|null $a
	 * @param T $b
	 * @return T
	 */
	public function twoParams($a, $b)
	{
		return $b;
	}

	/**
	 * @template T
	 * @param T|null $x
	 * @return list<T>
	 */
	public function wrapped($x = null): array
	{
		if ($x === null) {
			return [];
		}

		return [$x];
	}

	/**
	 * @template T
	 * @param T|null $x
	 * @return Collection<T>
	 */
	public function collection($x = null): Collection
	{
		return new Collection($x);
	}

}

function (?int $nullOrInt, int $int, Service $service): void {
	assertType('Bug8441\Collection<*NEVER*>', new Collection());
	assertType('Bug8441\Collection<*NEVER*>', new Collection(null));
	assertType('Bug8441\Collection<int>', new Collection($nullOrInt));
	assertType('Bug8441\Collection<int>', new Collection($int));
	assertType('Bug8441\CollectionWithNonNullableParam<null>', new CollectionWithNonNullableParam());
	assertType('Bug8441\CollectionWithNonNullableParam<null>', new CollectionWithNonNullableParam(null));

	assertType('mixed', nullableParam());
	assertType('mixed', nullableParam(null));
	assertType('int', nullableParam($nullOrInt));
	assertType('int', nullableParam($int));

	assertType('mixed', $service->method());
	assertType('mixed', $service->method(null));
	assertType('int', $service->method($nullOrInt));
	assertType('int', $service->method($int));

	assertType('mixed', Service::staticMethod());
	assertType('mixed', Service::staticMethod(null));
	assertType('int', Service::staticMethod($nullOrInt));
	assertType('int', Service::staticMethod($int));

	assertType('mixed', $service->falseParam());
	assertType('mixed', $service->falseParam(false));
	assertType('int', $service->falseParam($int));

	assertType('int', $service->twoParams(null, $int));
	assertType('int', $service->twoParams($int, $int));

	assertType('list<mixed>', $service->wrapped());
	assertType('list<mixed>', $service->wrapped(null));
	assertType('list<int>', $service->wrapped($int));

	assertType('Bug8441\Collection<mixed>', $service->collection());
	assertType('Bug8441\Collection<mixed>', $service->collection(null));
	assertType('Bug8441\Collection<int>', $service->collection($int));
};
