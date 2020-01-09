<?php

namespace UniversalObjectCreates;

class DifferentGetSetTypes
{
	private $values = [];

	public function __get($name): DifferentGetSetTypesValue
	{
		$this->values[$name] ?: new DifferentGetSetTypesValue();
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
