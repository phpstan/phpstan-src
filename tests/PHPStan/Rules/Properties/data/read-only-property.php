<?php

namespace ReadOnlyProperty;

class Foo
{

	private readonly int $foo;
	private readonly $bar;
	private readonly int $baz = 0;

}

final class ErrorResponse
{
	public function __construct(public readonly string $message = '')
	{
	}
}

class StaticReadonlyProperty
{
	private readonly static int $foo;
}
