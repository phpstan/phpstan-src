<?php declare(strict_types=1);

namespace Bug11088;

trait Foo
{
	protected const ARR1 = [
		self::KEY => 'int',
	];
}

class HelloWorld
{
	use Foo;

	protected const KEY = 'k1';

	protected const ARR = self::ARR1 + [
		'a.b' => 'int',
	];
}
