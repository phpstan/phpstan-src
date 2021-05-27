<?php declare(strict_types = 1);

namespace App\TraitUse;

use PHPStan\Type\JustNullableTypeTrait;

trait FooTrait
{

}

class Foo
{

	use JustNullableTypeTrait;
	use FooTrait;

}
