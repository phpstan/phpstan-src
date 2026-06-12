<?php // lint >= 8.1
namespace Bug13872;

use function is_array;
use function is_bool;
use function is_callable;
use function is_countable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_null;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function PHPStan\Testing\assertType;

class Foo
{
	public function check(): void
	{
		assertType('true', is_callable(is_callable(...)));
		assertType('true', is_callable(is_array(...)));
		assertType('false', is_array(is_string(...)));
		assertType('false', is_string(is_int(...)));
		assertType('false', is_int(is_callable(...)));
		assertType('true', is_object(is_callable(...)));
		assertType('true', is_callable(is_bool(...)));
		assertType('false', is_null(is_string(...)));
		assertType('false', is_float(is_int(...)));
		assertType('false', is_scalar(is_numeric(...)));
		assertType('false', is_countable(is_array(...)));
		assertType('false', is_iterable(is_string(...)));
		assertType('false', is_resource(is_callable(...)));
	}
}
