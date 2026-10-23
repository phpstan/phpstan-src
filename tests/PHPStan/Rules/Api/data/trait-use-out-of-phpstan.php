<?php declare(strict_types = 1);

namespace App\TraitUse;

use PHPStan\Type\JustNullableTypeTrait;
use PHPStan\Type\Traits\LateResolvableTypeTrait;

trait FooTrait
{

}

class Foo
{

	use JustNullableTypeTrait;
	use FooTrait;

}

class Bar
{

	use LateResolvableTypeTrait;

}
