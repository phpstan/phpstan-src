<?php declare(strict_types = 1);

namespace Bug15667Php7;

use function PHPStan\Testing\assertType;

class Foo
{
	public function takesString(string $s): void {}

	/** @param array<mixed> $fields */
	public function doFoo(array $fields): void
	{
		foreach ($fields as $key => $fieldset) {
			assertType('(int|string)', $key);
			switch ($key) {
				case 'shipping':
					break;
				case 'billing':
					break;
			}
			assertType('(int|string)', $key);
			$this->takesString($key);
		}
	}
}
