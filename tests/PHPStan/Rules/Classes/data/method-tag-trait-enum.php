<?php // lint >= 8.1

namespace MethodTagTraitEnum;

/**
 * @method intt doFoo()
 */
trait Foo
{

}

enum FooEnum
{

	use Foo;

}
