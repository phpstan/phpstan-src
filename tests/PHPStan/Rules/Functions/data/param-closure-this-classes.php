<?php

namespace ParamClosureThisClassesFunctions;

trait FooTrait
{

}

class Foo
{

}

/**
 * @param-closure-this Nonexistent $a
 * @param-closure-this FooTrait $b
 * @param-closure-this fOO $c
 */
function doFoo(
	callable $a,
	callable $b,
	callable $c
): void
{

}
