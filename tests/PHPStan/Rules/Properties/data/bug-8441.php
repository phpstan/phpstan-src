<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug8441Properties;

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

	/** @var Collection<int> */
	private readonly Collection $collection2;

	public function __construct() {
		$this->collection = new Collection();
		$this->collection2 = new Collection(null);
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
		// error here :(
		$this->collection = new CollectionWithNonNullableParam(); // Property Test::$collection (Collection<int>) does not accept Collection<null>.
	}
}
