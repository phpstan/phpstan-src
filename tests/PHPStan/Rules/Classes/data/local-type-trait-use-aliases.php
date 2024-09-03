<?php

namespace LocalTypeTraitUseAliases;

trait SomeTrait
{

}

/**
 * @phpstan-type A = Nonexistent
 * @phpstan-type B = SomeTrait
 * @phpstan-type C = string&int
 * @phpstan-type D = \Exception<int>
 */
trait Foo
{

}

class Usage
{

	use Foo;

}
