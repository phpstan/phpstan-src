<?php // lint <8.0

namespace Bug4457Php7;

use function PHPStan\Testing\assertType;

class Foo {
	public function invalidOperator() {
		assertType('null', version_compare('1.0', '1.1', 'a'));
	}

	public function operatorCanBeNull() {
		assertType('bool', version_compare('1.0', '1.1', null));
	}

	function compare(string $version1, string $version2, ?string $comparator = null)
	{
		assertType('-1|0|1|bool|null', version_compare($version1, $version2, $comparator));
	}
}
