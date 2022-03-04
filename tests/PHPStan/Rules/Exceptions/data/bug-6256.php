<?php // lint >= 7.4

namespace Bug6256;

final class A
{
	public int $intVal = 1;

	function doFoo()
	{
		try {
			$this->intVal = "string";
		} catch (\TypeError $e) {
		}
	}
}

final class B
{
	public $noType;

	function doFoo()
	{
		try {
			$this->noType = "string";
		} catch (\TypeError $e) {
		}
	}
}

final class C
{
	public string $stringType;

	function doFoo()
	{
		try {
			$this->stringType = "string";
		} catch (\TypeError $e) {
		}
	}
}
