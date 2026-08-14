<?php declare(strict_types = 1);

namespace Bug11953;

use Closure;
use function PHPStan\Testing\assertType;

class Foo
{

	public int $id = 1;

}

$foo = new Foo();

$closure = Closure::bind(
	fn () => $this->id,
	$foo,
	Foo::class,
);

assertType('((Closure(): int)|null)', $closure);
