<?php // lint >= 7.4

namespace Bug6256;

final class A
{
	public int $integerType = 1;
	public $mixedType;
	public string $stringType;
	/** @var string|int */
	public $stringOrIntType;

	function doFoo()
	{
		try {
			$this->integerType = "string";
		} catch (\TypeError $e) {
		}

		try {
			$this->mixedType = "string";
		} catch (\TypeError $e) {
		}

		try {
			$this->stringType = "string";
		} catch (\TypeError $e) {
		}

		/** @var string|int $intOrString */
		$intOrString = '';
		try {
			$this->integerType = $intOrString;
		} catch (\TypeError $e) {
		}

		try {
			$this->stringOrIntType = 1;
		} catch (\TypeError $e) {
		}

		try {
			$this->integerType = "string";
		} catch (\Error $e) {
		}

		try {
			$this->integerType = "string";
		} catch (\Exception $e) {
		}

		try {
			$this->dynamicProperty = 1;
		} catch (\Throwable $e) {
		}
	}
}
