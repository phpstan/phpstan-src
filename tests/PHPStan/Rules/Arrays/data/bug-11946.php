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
		string $lowerCase
	)
	{
		echo $s[-1];
		echo $numericS[-1];
		echo $nonEmpty[-1];
		echo $nonFalsy[-1];
		echo $lowerCase[-1];

		$s = 'hi';
		echo $s[1];
		echo $s[10];
	}

	/**
	 * @param numeric-string $numericS
	 * @param non-empty-string $nonEmpty
	 * @param non-falsy-string $nonFalsy
	 * @param lowercase-string $lowerCase
	 *
	 * @param int<-5, 5> $maybeWrong
	 */
	public function maybeNonExistentStringOffset(
		string $s,
		string $numericS,
		string $nonEmpty,
		string $nonFalsy,
		string $lowerCase,
	    int    $maybeWrong, int $oneToTwo
	)
	{
		echo $s[$maybeWrong];
		echo $numericS[$maybeWrong];
		echo $nonEmpty[$maybeWrong];
		echo $nonFalsy[$maybeWrong];
		echo $lowerCase[$maybeWrong];

		$s = 'hia';
		echo $s[$maybeWrong];
		if ($maybeWrong >= 1 && $maybeWrong < 3) {
			echo $s[$maybeWrong];
		}
	}
}
