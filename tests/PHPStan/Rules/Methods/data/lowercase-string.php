<?php

namespace LowercaseString;

class Bar
{
	/** @param lowercase-string $s */
	public function acceptLowercaseString(string $s): void
	{
	}

	/**
	 * @param string $string
	 * @param lowercase-string $lowercaseString
	 * @param numeric-string $numericString
	 * @param non-empty-lowercase-string $nonEmptyLowercaseString
	 *
	 * @return void
	 */
	public function test(
		string $string,
		string $lowercaseString,
		string $numericString,
		string $nonEmptyLowercaseString
	): void {
		$this->acceptLowercaseString('NotLowerCase');
		$this->acceptLowercaseString('lowercase');
		$this->acceptLowercaseString($string);
		$this->acceptLowercaseString($lowercaseString);
		$this->acceptLowercaseString($numericString);
		$this->acceptLowercaseString($nonEmptyLowercaseString);
	}
}
