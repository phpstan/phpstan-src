<?php // lint >= 7.4

namespace Bug6256;

use Exception;

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
			// not dead
		}

		try {
			$this->mixedType = "string";
		} catch (\TypeError $e) {
			// dead
		}

		try {
			$this->stringType = "string";
		} catch (\TypeError $e) {
			// dead
		}

		/** @var string|int $intOrString */
		$intOrString = '';
		try {
			$this->integerType = $intOrString;
		} catch (\TypeError $e) {
			// not dead
		}

		try {
			$this->stringOrIntType = 1;
		} catch (\TypeError $e) {
			// dead
		}

		try {
			$this->integerType = "string";
		} catch (\Error $e) {
			// not dead
		}

		try {
			$this->integerType = "string";
		} catch (\Exception $e) {
			// dead
		}

		try {
			$this->dynamicProperty = 1;
		} catch (\Throwable $e) {
			// dead
		}
	}
}

final class B {

	/**
	 * @throws Exception
	 */
	public function __set(string $name, $value)
	{
		throw new Exception();
	}

	function doFoo()
	{
		try {
			$this->dynamicProperty = "string";
		} catch (\Exception $e) {
			// not dead
		}
	}
}

final class C {

	/**
	 * @throws void
	 */
	public function __set(string $name, $value) {}

	function doFoo()
	{
		try {
			$this->dynamicProperty = "string";
		} catch (\Exception $e) {
			// dead
		}
	}
}

class D {
	function doFoo()
	{
		try {
			$this->dynamicProperty = "string";
		} catch (\Exception $e) {
			// not dead because class is not final
		}
	}
}
