<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug10312c;

enum Foo: int
{
	case BAR = 1;
	case BAZ = 2;
}

interface ReturnsFoo
{
	/** @return value-of<Foo> */
	public function returnsFooValue(): int;
}

class ReturnsBar implements ReturnsFoo
{
	#[\Override]
	public function returnsFooValue(): int
	{
		return Foo::BAR->value;
	}
}

class ReturnsBarWithFinalMethod implements ReturnsFoo
{
	#[\Override]
	final public function returnsFooValue(): int
	{
		return Foo::BAR->value;
	}
}

final class ReturnsBaz implements ReturnsFoo
{
	#[\Override]
	public function returnsFooValue(): int
	{
		return Foo::BAZ->value;
	}
}
