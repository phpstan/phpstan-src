<?php // lint <8.0

namespace Bug4457Php7;

use function PHPStan\Testing\assertType;

class Foo {
	public function invalidOperator() {
		assertType('null', version_compare('1.0', '1.1', 'a'));
	}

	public function operatorCanBeNull() {
		assertType('true', version_compare('1.0', '1.1', null));
	}

	/**
	 * @return int|bool
	 */
	function compare(string $version1, string $version2, ?string $comparator = null)
	{
		$result = version_compare($version1, $version2, $comparator);

		if (null === $result) {
			throw new InvalidArgumentException(sprintf('Unknown comparator "%s".', $comparator));
		}

		return $result;
	}
}
