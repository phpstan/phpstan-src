<?php

namespace Bug10922;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @param array<string, array{foo: string}> $array */
	public function sayHello(array $array): void
	{
		foreach ($array as $key => $item) {
			$array[$key]['bar'] = '';
		}
		assertType("array<string, array{foo: string, bar: ''}>", $array);
	}

	/** @param array<string, array{foo: string}> $array */
	public function sayHello2(array $array): void
	{
		if (count($array) > 0) {
			return;
		}

		foreach ($array as $key => $item) {
			$array[$key]['bar'] = '';
		}
		assertType("array{}", $array);
	}

	/** @param array<string, array{foo: string}> $array */
	public function sayHello3(array $array): void
	{
		if (count($array) === 0) {
			return;
		}

		foreach ($array as $key => $item) {
			$array[$key]['bar'] = '';
		}
		assertType("non-empty-array<string, array{foo: string, bar: ''}>", $array);
	}
}
