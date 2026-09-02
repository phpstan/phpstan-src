<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug8441Methods;

/**
 * @template T
 */
class Collection {
	/**
	 * @param T|null $t
	 */
	public function __construct(private readonly mixed $t = null) {}
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

class Service
{

	/**
	 * @template T
	 * @param T|null $x
	 * @return T
	 */
	public function identity($x = null)
	{
		if ($x === null) {
			throw new \LogicException();
		}

		return $x;
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

class Consumer
{

	/** @param Collection<int> $c */
	public function takeInts(Collection $c): void
	{
	}

	/** @param CollectionWithNonNullableParam<int> $c */
	public function takeIntsNonNullableParam(CollectionWithNonNullableParam $c): void
	{
	}

	public function takeInt(int $i): void
	{
	}

	public function doFoo(Service $service, ?int $nullOrInt, int $int): void
	{
		$this->takeInts(new Collection());
		$this->takeInts(new Collection(null));
		$this->takeInts(new Collection($nullOrInt));
		$this->takeInts(new Collection($int));
		$this->takeInts(new Collection('foo'));

		$this->takeIntsNonNullableParam(new CollectionWithNonNullableParam());
		$this->takeIntsNonNullableParam(new CollectionWithNonNullableParam(null));
		$this->takeIntsNonNullableParam(new CollectionWithNonNullableParam($int));

		$this->takeInts($service->collection());
		$this->takeInts($service->collection(null));
		$this->takeInts($service->collection($int));
		$this->takeInts($service->collection('foo'));

		$this->takeInt($service->identity($int));
		$this->takeInt($service->identity($nullOrInt));
		$this->takeInt($service->identity('foo'));
	}

}
