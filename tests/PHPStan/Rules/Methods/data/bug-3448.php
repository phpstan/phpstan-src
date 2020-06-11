<?php

namespace Bug3448;

use function func_get_args;

final class Foo
{
	public static function add(int $lall): void
	{
		$args = func_get_args();
	}
}

final class UseFoo
{
	public static function do(): void
	{
		Foo::add(1, [new \stdClass()]);

		Foo::add('foo');
		Foo::add('foo', [new \stdClass()]);
	}
}
