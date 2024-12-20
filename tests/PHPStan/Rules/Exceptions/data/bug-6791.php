<?php

namespace Bug6791;

final class Foo {
	/** @var int[]  */
	public array $intArray;
	/** @var \Ds\Set<int> */
	public \Ds\Set $set;
	/** @var int[]  */
	public $array;
}

class Bar
{

	public function doFoo()
	{
		$foo = new Foo();
		try {
			$foo->intArray = ["a"];
		} catch (\TypeError $e) {}

		try {
			$foo->set = ["a"];
		} catch (\TypeError $e) {}

		try {
			$foo->set = new \Ds\Set;
		} catch (\TypeError $e) {}

		try {
			$foo->array = ["a"];
		} catch (\TypeError $e) {}

		try {
			$foo->array = "non-array";
		} catch (\TypeError $e) {}
	}

}


