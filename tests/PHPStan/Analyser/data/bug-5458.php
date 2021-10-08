<?php // lint >= 7.4

namespace Bug5458;

use function PHPStan\Testing\assertType;

class Foo
{

	const A = [
		1,
		2,
		3,
	];

	const B = [
		...self::A,
	];

	public function doFoo()
	{
		assertType('array(1, 2, 3)', self::A);
		assertType('array(1, 2, 3)', self::B);
	}

}

const A = [
	'a',
	'b',
	'c',
];

const B = [
	...A
];

function doFoo()
{
	assertType('array(\'a\', \'b\', \'c\')', A);
	assertType('array(\'a\', \'b\', \'c\')', B);
}
