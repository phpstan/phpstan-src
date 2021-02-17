<?php declare(strict_types = 1);

namespace Bug3544;

class HelloWorld
{
	/**
	 * @param array<string, string> $input
	 */
	public function foo(array $input): void
	{
		if( ! array_key_exists( 'foo', $input)) {
			throw new \LogicException();
		}

		unset($input['foo']);

		if($input === []) {
			echo 'hello';
		}
	}
}
