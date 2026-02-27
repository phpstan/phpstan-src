<?php declare(strict_types = 1);

namespace Bug7388;

interface FooInterface
{
	public function bar(int $i): void;
}

class ParentFoo
{
	public function bar(): void
	{
	}
}

class Foo extends ParentFoo implements FooInterface
{
}

// Additional cases

interface BazInterface
{
	public function baz(string $s, int $i): void;
}

class ParentBaz
{
	public function baz(string $s): void
	{
	}
}

class Baz extends ParentBaz implements BazInterface
{
}

// Case where the parent has more params than the interface (should be ok in some cases)
interface QuxInterface
{
	public function qux(): void;
}

class ParentQux
{
	public function qux(int $i = 0): void
	{
	}
}

class Qux extends ParentQux implements QuxInterface
{
}

// Case where the child DOES override (already handled by existing rules)
class OverridingFoo extends ParentFoo implements FooInterface
{
	public function bar(int $i): void
	{
	}
}

// Abstract class should not be checked (it can defer implementation)
abstract class AbstractFoo extends ParentFoo implements FooInterface
{
}
