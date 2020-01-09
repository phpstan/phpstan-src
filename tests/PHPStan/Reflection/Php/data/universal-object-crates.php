<?php

namespace UniversalObjectCreates;

class DifferentGetSetTypes
{
	private $values = [];

	public function __get($name): DifferentGetSetTypesValue
	{
		return $this->values[$name] ?: new DifferentGetSetTypesValue();
	}

	public function __set($name, string $value): void
	{
		$newValue = new DifferentGetSetTypesValue();
		$newValue->value = $value;

		$this->values[$name] = $newValue;
	}
}

class DifferentGetSetTypesValue
{
	public $value = null;
}

class NativeProperty
{
	/** @var array */
	public $nativeProperty;

	public function __get($name): int
	{
		return 42;
	}
}
