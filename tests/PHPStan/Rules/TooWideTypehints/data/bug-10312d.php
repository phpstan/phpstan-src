<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug10312d;

enum Foo: int
{
	case BAR = 1;
	case BAZ = 2;
}

class FooBar {
	public ?Foo $foo = null;
}

interface ReturnsFoo
{
	/** @return value-of<Foo> */
	public function returnsFooValue(): int;

	/** @return value-of<Foo>|null */
	public function returnsFooOrNullValue(): ?int;
}

final class ReturnsNullsafeBaz implements ReturnsFoo
{
	#[\Override]
	public function returnsFooValue(): int
	{
		$f = new FooBar();
		return $f->foo?->value;
	}

	#[\Override]
	public function returnsFooOrNullValue(): ?int
	{
		$f = new FooBar();
		return $f->foo?->value;
	}
}
