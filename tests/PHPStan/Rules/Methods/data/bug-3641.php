<?php

namespace Bug3641;

class Foo
{
	public function bar(): int
	{
		return 5;
	}
}

/**
 * @mixin Foo
 */
class Bar
{
	/**
	 * @param  mixed[]  $args
	 * @return mixed
	 */
	public static function __callStatic(string $method, $args)
	{
		$instance = new Foo;

		return $instance->$method(...$args);
	}
}

function (): void {
	Bar::bar();
	Bar::bar(1);
};
