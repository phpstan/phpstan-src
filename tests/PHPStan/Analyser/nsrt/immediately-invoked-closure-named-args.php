<?php // lint >= 8.0

namespace ImmediatelyInvokedClosureNamedArgs;

use function PHPStan\Testing\assertType;

class Foo
{

	public function namedArgAfterPositionalArgs(): void
	{
		(function ($a, $b = 0, $c = 0) {
			assertType('1', $a);
			assertType('mixed', $b);
			assertType("'x'", $c);
		})(...[1], c: 'x');

		(function ($a, $b = 0, $c = 0) {
			assertType('1', $a);
			assertType('mixed', $b);
			assertType("'x'", $c);
		})(1, c: 'x');

		(function ($a, $b) {
			assertType("'x'", $a);
			assertType('1', $b);
		})(b: 1, a: 'x');
	}

}
