<?php

namespace UppercaseString;

class Bar
{
	/** @param uppercase-string $s */
	public function acceptUppercaseString(string $s): void
	{
	}

	/**
	 * @param string $string
	 * @param uppercase-string $uppercaseString
	 * @param numeric-string $numericString
	 * @param non-empty-uppercase-string $nonEmptyUppercaseString
	 *
	 * @return void
	 */
	public function test(
		string $string,
		string $uppercaseString,
		string $numericString,
		string $nonEmptyUppercaseString
	): void {
		$this->acceptUppercaseString('NotUpperCase');
		$this->acceptUppercaseString('UPPERCASE');
		$this->acceptUppercaseString($string);
		$this->acceptUppercaseString($uppercaseString);
		$this->acceptUppercaseString($numericString);
		$this->acceptUppercaseString($nonEmptyUppercaseString);
	}
}
