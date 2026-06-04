<?php

namespace CallToConstructorWithoutImpurePointsThrows;

class InvalidFileInfo extends \Exception
{

}

class FileIteratorSourceLocator
{

	/**
	 * @param array<int> $ints
	 * @throws InvalidFileInfo
	 */
	public function __construct(array $ints)
	{
		foreach ($ints as $int) {
			if (!is_int($int)) {
				throw new InvalidFileInfo();
			}
		}
	}

}

class NoThrows
{

	public function __construct()
	{
	}

}

function (): void {
	new FileIteratorSourceLocator([1, 2, 3]);
	new NoThrows();
};
