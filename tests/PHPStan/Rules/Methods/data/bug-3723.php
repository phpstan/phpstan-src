<?php

namespace Bug3723;

class HelloWorld
{
	/**
	 * @param array<string, mixed> $bar The raw frame
	 *
	 * @psalm-param array{
	 *     foo?: Foo::TEST,
	 *     bar: string
	 * } $bar
	 */
	public function foo(array $bar): void
	{
	}
}
