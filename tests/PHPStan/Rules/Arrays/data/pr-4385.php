<?php

namespace Pr4385;

class Foo
{
	/**
	 * @param array<int> $array
	 * @param int   $int
	 * @param string $string
	 * @param object $object
	 * @param array{0: string} $constantArray
	 *
	 * @return void
	 */
	public function test($array, $int, $string, $object, $constantArray)
	{
		$arrayOrObject = rand(0, 1) ? $array : $object;
		$arrayOrInt = rand(0, 1) ? $array : $int;
		$arrayOrString = rand(0, 1) ? $array : $string;
		$arrayOrZero = rand(0, 1) ? $array : 0;

		$array[$array];
		$array[$int];
		$array[$string];
		$array[$object]; // Reported by InvalidKeyInArrayDimFetchRule
		$array[$arrayOrObject]; // Reported by InvalidKeyInArrayDimFetchRule
		$array[$arrayOrInt];
		$array[$arrayOrString];
		$array[$arrayOrZero];

		$constantArray[$array];
		$constantArray[$int];
		$constantArray[$string];
		$constantArray[$object]; // Reported by InvalidKeyInArrayDimFetchRule
		$constantArray[$arrayOrObject]; // Reported by InvalidKeyInArrayDimFetchRule
		$constantArray[$arrayOrInt];
		$constantArray[$arrayOrString];
		$constantArray[$arrayOrZero]; // Reported by InvalidKeyInArrayDimFetchRule

		$arrayOrString[$arrayOrInt];
	}
}
