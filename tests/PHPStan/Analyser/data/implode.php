<?php declare(strict_types = 1);

namespace ImplodeFunctionReturn;

use function PHPStan\Testing\assertType;

class Foo
{
	const X = 'x';
	const ONE = 1;

	public function constants() {
		assertType("'12345'", implode(['12', '345']));

		assertType("'12345'", implode('', ['12', '345']));
		assertType("'12345'", join('', ['12', '345']));

		assertType("'12,345'", implode(',', ['12', '345']));
		assertType("'12,345'", join(',', ['12', '345']));

		assertType("'x,345'", join(',', [self::X, '345']));
		assertType("'1,345'", join(',', [self::ONE, '345']));
	}
}
