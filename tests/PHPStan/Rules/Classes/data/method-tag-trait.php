<?php

namespace MethodTagTrait;

/**
 * @method intt doFoo()
 * @method int doBar(int&string $a)
 * @method int doBaz(int $a = ClassWithConstant::FOO)
 * @method int doBaz2(int $a = ClassWithConstant::BAR)
 * @method array doMissingIterablueValue()
 */
trait Foo
{

}


class ClassWithConstant
{

	public const FOO = 1;

}

class Usages
{

	use Foo;

}
