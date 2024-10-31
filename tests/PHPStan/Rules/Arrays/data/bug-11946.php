<?php declare(strict_types = 1);

namespace Bug11946;

class HelloWorld
{
	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param lowercase-string $lowerCase
	 */
	public function nonExistentStringOffset(
		string $s,
		string $numericS,
		string $nonEmpty,
		string $nonFalsy,
		string $lowerCase,
	)
	{
		echo $s[-1];
		echo $numericS[-1];
		echo $nonEmpty[-1];
		echo $nonFalsy[-1];
		echo $lowerCase[-1];

		echo $s[0];
		echo $numericS[0];
		echo $nonEmpty[0];
		echo $nonFalsy[0];
		echo $lowerCase[0];
	}
}
