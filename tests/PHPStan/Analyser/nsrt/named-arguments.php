<?php // lint >= 8.0

namespace NamedArguments;

use function PHPStan\Testing\assertType;

class Foo
{
	public function array_search() {
		$haystack = ['a', 'b', 'c'];
		$needle = 'c';
		assertType('2', array_search(strict: true, needle: $needle, haystack: $haystack));
	}
}
