<?php // lint >= 8.1

namespace Bug14481;

class VehicleType {
	/** @var array<int, string> */
	public array $brand = [];
}

class Vehicle {
	public int $id = 1;
	public string $name = 'test';
}

final readonly class VehicleListFilterForModule
{
	/**
	 * @var array<int, VehicleType>
	 */
	private array $carTypes;

	/** @param array<int, VehicleType> $carTypes */
	public function __construct(array $carTypes, Vehicle $vehicle, int $vehicleTyp)
	{
		$this->carTypes = $carTypes;

		$this
			->carTypes[$vehicleTyp]
			->brand[$vehicle->id] = $vehicle->name;
	}
}

class IndirectModificationOutsideConstructor
{
	public readonly array $items;

	/**
	 * @param array<int, VehicleType> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand[0] = 'test';
	}
}

class DirectPropertyAssignThroughReadonlyArray
{
	public readonly array $items;

	/**
	 * @param array<int, VehicleType> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand = ['test'];
	}
}

class NestedArrayDimFetch
{
	/**
	 * @var array<int, array<int, VehicleType>>
	 */
	public readonly array $nested;

	/**
	 * @param array<int, array<int, VehicleType>> $nested
	 */
	public function __construct(array $nested)
	{
		$this->nested = $nested;
	}

	public function doFoo(int $key1, int $key2): void
	{
		$this->nested[$key1][$key2]->brand[0] = 'test';
	}
}

class NonReadonlyIsOk
{
	/** @var array<int, VehicleType> */
	public array $items = [];

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand[0] = 'test';
	}
}

class ArrayAccessIsOk
{
	public readonly \ArrayObject $storage;

	public function __construct()
	{
		$this->storage = new \ArrayObject();
	}

	public function doFoo(): void
	{
		$this->storage[0]->brand[0] = 'test';
	}
}

class IncrementThroughReadonlyArray
{
	/** @var array<int, VehicleType> */
	public readonly array $items;

	/**
	 * @param array<int, VehicleType> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand[0] .= 'suffix';
	}
}

class ReadonlyObjectPropertyIsOk
{
	public readonly VehicleType $vehicle;

	public function __construct(VehicleType $vehicle)
	{
		$this->vehicle = $vehicle;
	}

	public function doFoo(): void
	{
		$this->vehicle->brand[0] = 'test';
	}
}

class DeeperChain
{
	/** @var array<int, Wrapper> */
	public readonly array $wrappers;

	/**
	 * @param array<int, Wrapper> $wrappers
	 */
	public function __construct(array $wrappers)
	{
		$this->wrappers = $wrappers;
	}

	public function doFoo(int $key): void
	{
		$this->wrappers[$key]->vehicle->brand[0] = 'test';
	}
}

class Wrapper
{
	public VehicleType $vehicle;

	public function __construct(VehicleType $vehicle)
	{
		$this->vehicle = $vehicle;
	}
}

/** @readonly */
class ReadonlyByPhpDocClass
{
	/** @var array<int, VehicleType> */
	public array $items;

	/**
	 * @param array<int, VehicleType> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand[0] = 'test';
	}
}

class ReadonlyByPhpDocProperty
{
	/**
	 * @readonly
	 * @var array<int, VehicleType>
	 */
	public array $items;

	/**
	 * @param array<int, VehicleType> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function doFoo(int $key): void
	{
		$this->items[$key]->brand[0] = 'test';
	}
}
