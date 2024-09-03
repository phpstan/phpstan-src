<?php

namespace PropertyTagTrait;

/**
 * @property intt $foo
 * @property array $bar
 */
trait Foo
{

}

class Usages
{

	use Foo;

}
