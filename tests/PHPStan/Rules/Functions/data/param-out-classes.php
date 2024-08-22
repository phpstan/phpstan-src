<?php

namespace ParamOutClasses;

trait FooTrait
{

}

class Foo
{

}

/**
 * @param-out Nonexistent $p
 * @param-out FooTrait $q
 * @param-out fOO $r
 */
function doFoo(&$p, &$q, $r): void
{

}
