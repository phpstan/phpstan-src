<?php // lint >= 8.0

namespace ExplodePhp8;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param non-empty-string $nonEmptyString
	 */
	public function constantArrays(string $string, string $nonEmptyString): void
	{
		$strings = explode(',', $string, 0);
		assertType('array{string}', $strings);

		$strings = explode(',', $string, 2);
		assertType('array{0: string, 1?: string}', $strings);

		$strings = explode(rand(0, 1) ? '' : ',', $string, 2);
		assertType('array{0: string, 1?: string}', $strings);

		$strings = explode(',', $string, 16);
		assertType('non-empty-list<string>', $strings);

		$strings = explode(',', $nonEmptyString, 2);
		assertType('array{0: string, 1?: string}', $strings);

		$strings = explode(',', $nonEmptyString, 16);
		assertType('non-empty-list<string>', $strings);
	}

}
