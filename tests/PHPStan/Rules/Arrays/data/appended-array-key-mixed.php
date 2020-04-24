<?php

namespace AppendedArrayKeyMixed;

class Foo
{

	/** @var array<int, mixed> */
	private $intArray;

	/** @var array<string, mixed> */
	private $stringArray;

	/** @var array<int|string, mixed> */
	private $bothArray;

	/**
	 * @param mixed $explicitMixed
	 */
	public function doFoo($explicitMixed, $notExplicitMixed)
	{
		$this->intArray[$explicitMixed] = 1;
		$this->intArray[$notExplicitMixed] = 1;
		$this->stringArray[$explicitMixed] = 1;
		$this->stringArray[$notExplicitMixed] = 1;
		$this->bothArray[$explicitMixed] = 1;
		$this->bothArray[$notExplicitMixed] = 1;
	}

}
