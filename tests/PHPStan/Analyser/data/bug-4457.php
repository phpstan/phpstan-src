<?php // lint >= 8.0

namespace Bug4457;

use function PHPStan\Testing\assertType;

class Foo {
	public function invalidOperator() {
		assertType('*ERROR*', version_compare('1.0', '1.1', 'a'));
	}

	public function operatorCanBeNull() {
		assertType('-1', version_compare('1.0', '1.1', null));
	}

	function compare(string $version1, string $version2, ?string $comparator = null)
	{
		assertType('bool', version_compare($version1, $version2, $comparator));
	}

	/**
	 * @return int|bool
	 */
	function compare4457(string $version1, string $version2, ?string $comparator = null)
	{
		$result = version_compare($version1, $version2, $comparator);

		if (null === $result) {
			throw new InvalidArgumentException(sprintf('Unknown comparator "%s".', $comparator));
		}

		assertType('bool', $result);

		return $result;
	}
}
