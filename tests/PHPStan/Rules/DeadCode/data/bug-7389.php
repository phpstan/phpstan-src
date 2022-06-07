<?php declare(strict_types = 1);

namespace Bug7389;

class HelloWorld
{
	/**
	 * @param string $test test
	 * @return array<string>
	 */
	private function getTest(string $test): array
	{
		return [
			'',
			'',
		];
	}

	/**
	 * @param string $test test
	 * @return string
	 */
	private function getTest1(string $test): string
	{
		return 'test1';
	}
}
