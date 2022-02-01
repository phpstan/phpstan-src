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
		assertType('-1|0|1|bool', version_compare($version1, $version2, $comparator));
	}
}
