<?php declare(strict_types=1);

namespace Bug7199;

class HelloWorld
{
	/**
	 * @param non-empty-string $string
	 */
	public function checkForPhpEmpty(string $string): bool
	{
		return empty($string);
	}
}
