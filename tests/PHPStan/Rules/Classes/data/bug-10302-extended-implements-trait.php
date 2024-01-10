<?php

namespace Bug10302ExtendedImplementsTrait;

interface Interface1
{

}

/** @phpstan-require-implements Interface1 */
trait Foo
{

}

trait Bar
{
	use Foo;
}

class Baz
{

	use Bar;

}

class Baz2 implements Interface1
{

	use Bar;

}
