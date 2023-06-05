<?php

namespace CheckTypeFunctionCallNotPhpDoc;

class Foo
{

	/**
	 * @param int $phpDocInteger
	 */
	public function doFoo(
		int $realInteger,
		$phpDocInteger
	)
	{
		if (is_int($realInteger)) {

		}
		if (is_int($phpDocInteger)) {

		}
	}

	/** @param array<string> $strings */
	public function checkInArray(int $i, array $strings): void
	{
		if (in_array($i, $strings, true)) {
		}

		if (in_array(1, $strings, true)) {
		}
	}

}
