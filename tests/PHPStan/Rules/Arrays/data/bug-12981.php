<?php declare(strict_types = 1);

namespace Bug12981;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param non-empty-array<scalar> $arr */
	public function sayHello(array $arr): void
	{
		echo $arr[array_rand($arr)];
		$randIndex = array_rand($arr);
		echo $arr[$randIndex];
	}

	/** @param non-empty-array<scalar> $arr */
	public function sayHello1(array $arr): void
	{
		$num = 1;
		echo $arr[array_rand($arr, $num)];
		$randIndex = array_rand($arr, $num);
		echo $arr[$randIndex];
	}

	/** @param non-empty-array<scalar> $arr */
	public function sayHello2(array $arr): void
	{
		$num = 5;
		echo $arr[array_rand($arr, $num)];
		$randIndex = array_rand($arr, $num);
		echo $arr[$randIndex];
	}

	/** @param non-empty-array<scalar> $arr */
	public function sayHello4(array $arr, int $num): void
	{
		echo $arr[array_rand($arr, $num)];
		$randIndex = array_rand($arr, $num);
		echo $arr[$randIndex];
	}
}
