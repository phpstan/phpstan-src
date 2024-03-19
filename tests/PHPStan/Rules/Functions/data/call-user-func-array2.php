<?php // lint >= 8.0

namespace CallUserFuncArrayRule;

use function call_user_func_array;

class Foo
{

	public function doFoo(): void
	{
		$f = function (int $i): void {

		};
		call_user_func_array($f, []);
		call_user_func_array($f, [1]);
		call_user_func_array($f, ['foo']);
		call_user_func_array($f, ['i' => 'foo']);
		call_user_func_array(['i'=> 'foo'], callback: $f);
		call_user_func_array($f, ['i'=> 1]);
		call_user_func_array(['i'=> 1], callback: $f);
		call_user_func_array($f, ['j'=> 1]);
	}

	public function doBar(): void
	{
		$f = function (int $i, $j, $g = 2, $h = 3): void {
		};

		call_user_func_array($f);
		call_user_func_array($f, [1]);
		call_user_func_array($f, [2, 'foo']);
	}

	public function doVariadic(): void
	{
		$f = function ($i, $j, ...$params): void {
		};

		call_user_func_array($f);
		call_user_func_array($f, [1]);
		call_user_func_array($f, [2, 'foo']);
		$result = call_user_func_array($f, [2, 'foo']);
	}

}
