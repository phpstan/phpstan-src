<?php // lint >= 7.4

namespace ArrowFunctionsReturnTypes;

class Foo
{

	public function doFoo(int $i)
	{
		fn() => $i;
		fn(): int => $i;
		fn(): string => $i;
		fn(int $a): int => $a;
		fn(string $a): int => $a;
	}

}

class Bar
{

	public function doFoo(): void
	{

	}

	public function doBar(): void
	{
		fn () => $this->doFoo();
		fn (?string $value): string => $value ?? '-';
	}

}

static fn (int $value): iterable => yield $value;
