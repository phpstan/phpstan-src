<?php declare(strict_types = 1);

namespace Bug7968;

class HelloWorld
{
	/**
	 * @param array{name: string, age: int} $a
	 * @param array{name: string, age: int} $b
	 */
	public function compare(array $a, array $b): int
	{
		$sort = [
			$a['name'] <=> $b['name'],
			$a['age'] <=> $b['age'],
		];

		$sort = array_filter($sort, function (int $value): bool {
			return $value !== 0;
		});

		return array_shift($sort) ?? 0;
	}
}
