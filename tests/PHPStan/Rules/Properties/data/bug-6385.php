<?php // lint >= 8.1

namespace Bug6385;

use BackedEnum;
use UnitEnum;

final class EnumValue
{
	public readonly string $name;
	public readonly string $value;

	public function __construct(
		BackedEnum | string $name,
		BackedEnum | string $value
	) {
		$this->name = $name instanceof BackedEnum ? $name->name : $name;
		$this->value = $value instanceof BackedEnum ? $value->name : $value;
	}
}

enum ActualUnitEnum
{

}

enum ActualBackedEnum: int
{

}

class Foo
{

	public function doFoo(
		UnitEnum $unitEnum,
		BackedEnum $backedEnum,
		ActualUnitEnum $actualUnitEnum,
		ActualBackedEnum $actualBackedEnum
	)
	{
		echo $unitEnum->name;
		echo $unitEnum->value;
		echo $backedEnum->name;
		echo $backedEnum->value;
		echo $actualUnitEnum->name;
		echo $actualUnitEnum->value;
		echo $actualBackedEnum->name;
		echo $actualBackedEnum->value;
		
	}

}
