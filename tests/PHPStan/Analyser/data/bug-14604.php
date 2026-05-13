<?php

namespace Bug14604;

final class A
{
	/** @return array{} */
	public function selectedSearchValues(): array
	{
		/** @var array{from: string, to: string} $dates */
		$dates = ($_GET['dates'] ?? []) ?: throw new \Exception('No Dates selected');
		if (empty($dates['from']) || empty($dates['to'])) {
			throw new \Exception('Dates not selected');
		}

		/** @var array{latitude: string, longitude: string} $dates */
		$locations = ($_GET['location'] ?? []) ?: throw new \Exception('No Location selected');

		return [];
	}
}

final class B
{
	/** @return array<string, string> */
	public function mixedKeyEmpty(): array
	{
		/** @var array<string, string> $foo */
		$foo = ($_GET['foo'] ?? []) ?: throw new \Exception();
		$dynKey = (string) $_GET['k'];
		if (empty($foo['a']) || empty($foo[$dynKey])) {
			throw new \Exception();
		}

		return $foo;
	}
}

final class C
{
	/** @return array<int, string> */
	public function countThenEmpty(): array
	{
		/** @var array<int, string> $foo */
		$foo = ($_GET['foo'] ?? []) ?: throw new \Exception();
		if (count($foo) >= 2) {
			if (empty($foo[0])) {
				throw new \Exception();
			}

			return $foo;
		}

		return [];
	}
}
