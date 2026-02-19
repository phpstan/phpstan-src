<?php // lint >= 8.5

namespace Bug14150PipeOperator;

class Foo
{

	/**
	 * @param callable(string): string $cb
	 */
	public function doFoo(callable $cb): void
	{
		$a = 'hello';
		$a |> $cb
			|> $cb
			|> $cb
			|> self::doBar(...);
	}

	public static function doBar(string &$s): void
	{

	}

}
