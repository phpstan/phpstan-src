<?php

namespace Bug10302ExtendedTrait;

class Father
{

}

/** @phpstan-require-extends Father */
trait Foo
{

}

trait Bar
{
	use Foo;
}

class Baz // should report: Trait Foo requires using class to extend Father, but Baz does not.
{

	use Bar;

}


class Baz2 extends Father
{

	use Bar;

}
