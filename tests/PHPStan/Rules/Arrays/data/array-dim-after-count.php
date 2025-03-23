<?php declare(strict_types = 1);

namespace ArrayDimFetchOnCount;

class HelloWorld
{
	/**
	 * @param list<string> $hellos
	 */
	public function works(array $hellos): string
	{
		if ($hellos === []) {
			return 'nothing';
		}

		$count = count($hellos) - 1;
		return $hellos[$count];
	}

	/**
	 * @param list<string> $hellos
	 */
	public function offByOne(array $hellos): string
	{
		$count = count($hellos);
		return $hellos[$count];
	}

	/**
	 * @param array<string> $hellos
	 */
	public function maybeInvalid(array $hellos): string
	{
		$count = count($hellos) - 1;
		echo $hellos[$count];

		if ($hellos === []) {
			return 'nothing';
		}

		$count = count($hellos) - 1;
		return $hellos[$count];
	}

}
