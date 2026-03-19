<?php declare(strict_types = 1);

namespace Bug14325;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $array
 */
function func(array $array): void
{
	assertType('list<string>', $array);
	$array[] = 'bar';
	assertType('non-empty-list<string>', $array);
}

/**
 * @param list<string> $array
 */
$func = function(array $array): void
{
	assertType('list<string>', $array);
	$array[] = 'bar';
	assertType('non-empty-list<string>', $array);
};

class Foo
{
	/**
	 * @param list<string> $array
	 */
	public function method(array $array): void
	{
		assertType('list<string>', $array);

		/**
		 * @param list<string> $inner
		 */
		$closure = function (array $inner): void {
			assertType('list<string>', $inner);
			$inner[] = 'baz';
			assertType('non-empty-list<string>', $inner);
		};
	}
}
