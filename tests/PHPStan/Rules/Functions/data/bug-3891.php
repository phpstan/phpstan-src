<?php

namespace Bug3891;

class HelloWorld
{
	/**
	 * @param bool[] $data
	 *
	 * @return string[]
	 */
	public function doSomething(array $data): array
	{
		return array_map(static function (bool $value): iterable {
			if ($value) {
				yield 'something';
				return;
			}

			yield 'something else';
		}, $data);
	}
}

class HelloWorld2
{
	/**
	 * @param bool[] $data
	 *
	 * @return string[]
	 */
	public function doSomething(array $data): array
	{
		return array_map(static function (bool $value): \Generator {
			if ($value) {
				yield 'something';
				return;
			}

			yield 'something else';
		}, $data);
	}
}
