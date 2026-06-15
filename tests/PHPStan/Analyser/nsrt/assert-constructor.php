<?php // lint >= 8.0

namespace AssertConstructor;

use function PHPStan\Testing\assertType;

class Person
{
	/**
	 * @phpstan-assert non-empty-string $firstName
	 */
	public function __construct(
		public string $firstName,
	) {
		assert($firstName !== '');
	}
}

function (string $firstName) {
	new Person($firstName);
	assertType('non-empty-string', $firstName);
};
