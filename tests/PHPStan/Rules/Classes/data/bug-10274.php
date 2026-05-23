<?php declare(strict_types = 1);

namespace Bug10274;

/**
 * @template T
 */
class AbstractArray {
	/**
	 * @var array<array-key, T>
	 */
	public array $data;

	/**
	 * @param array<array-key, T> $data
	 */
	public function __construct(array $data = [])
	{
		$this->data = $data;
	}
}

interface BaseCollectionInterface {}


/**
 * @template T
 * @extends AbstractArray<T>
 */
abstract class AbstractCollection extends AbstractArray implements BaseCollectionInterface {}

/**
 * @template T
 */
interface ConstructorDefiningInterface extends BaseCollectionInterface {
	/**
	 * @param array<array-key, T> $data
	 */
	public function __construct(array $data = []);
}

/**
 * @template T
 * @extends AbstractCollection<T>
 * @implements ConstructorDefiningInterface<T>
 */
abstract class IntermediateCollection extends AbstractCollection implements ConstructorDefiningInterface {}

/**
 * @template T
 * @extends IntermediateCollection<T>
 */
class SpecificCollection extends IntermediateCollection {
	public static function create(): static
	{
		return new static();
	}
}

/**
 * @template T
 * @extends SpecificCollection<T>
 */
class DeeplyNestedCollection extends SpecificCollection {
	public static function create(): static
	{
		return new static();
	}
}
