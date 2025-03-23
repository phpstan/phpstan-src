<?php // lint >= 8.0

declare(strict_types = 1);

namespace ArrayDimAfterArraySeach;

class HelloWorld
{
	public function doFoo(array $arr, string $needle): string
	{
		if (($key = array_search($needle, $arr, true)) !== false) {
			echo $arr[$key];
		}
	}

	public function doBar(array $arr, string $needle): string
	{
		$key = array_search($needle, $arr, true);
		if ($key !== false) {
			echo $arr[$key];
		}
	}

	public function doFooBar(array $arr, string $needle): string
	{
		if (($key = array_search($needle, $arr, false)) !== false) {
			echo $arr[$key];
		}
	}

	public function doBaz(array $arr, string $needle): string
	{
		if (($key = array_search($needle, $arr)) !== false) {
			echo $arr[$key];
		}
	}
}
