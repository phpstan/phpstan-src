<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug6697;

function foo() {
	$result = \is_subclass_of( '\\My\\Namespace\\MyClass', '\\My\\Namespace\\MyBaseClass', true);
}

trait H
{
	public static function isEnum(): bool
	{
		return \is_subclass_of(static::class, \UnitEnum::class);
	}

	public static function isBacked(): bool
	{
		return \is_subclass_of(static::class, \BackedEnum::class);
	}
}

enum BEnum: string
{
	use H;

	case BAR = "bar";
}

enum UEnum
{
	use H;

	case BAR;
}

