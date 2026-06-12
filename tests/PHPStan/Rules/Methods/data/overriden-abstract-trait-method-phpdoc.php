<?php // lint >= 8.0

namespace OverridenAbstractTraitMethodPhpDoc;

/**
 * @template DataArray of array<string, mixed>
 */
trait FooTrait
{
	/**
	 * Offset checker
	 *
	 * @phpstan-param Offset $offset
	 * @return bool
	 * @template Offset of key-of<DataArray>
	 */
	abstract public function offsetExists(mixed $offset): bool;
}

/**
 * @template DataArray of array<string, mixed>
 * @phpstan-type DataKey key-of<DataArray>
 * @phpstan-type DataValue DataArray[DataKey]
 */
class FooClass
{

	/** @phpstan-use FooTrait<DataArray> */
	use FooTrait;

	/** @phpstan-var DataArray|array{} */
	public array $data = [];


	/**
	 * Data checker
	 *
	 * @phpstan-param Offset $offset
	 * @return bool
	 * @template Offset of key-of<DataArray>
	 */
	public function offsetExists(mixed $offset): bool
	{
		return array_key_exists($offset, $this->data);
	}
}
