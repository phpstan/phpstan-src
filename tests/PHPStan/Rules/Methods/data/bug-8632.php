<?php declare(strict_types = 1);

namespace Bug8632;

class HelloWorld
{
	/**
	 * @return array{
	 *     id?: int,
	 *     categories?: string[],
	 * }
	 */
	public function test(bool $foo): array
	{
		if ($foo) {
			$arr = [
				'id'         => 1,
				'categories' => ['news'],
			];
		} else {
			$arr = [];
		}

		return array_merge($arr, []);
	}
}
