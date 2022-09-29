<?php // lint >= 8.2

namespace ReadOnlyPropertyReadonlyClass;

readonly class Foo
{

	private int $foo;
	private $bar;
	private int $baz = 0;

}

readonly final class ErrorResponse
{
	public function __construct(public string $message = '')
	{
	}
}

readonly class StaticReadonlyProperty
{
	private static int $foo;
}
