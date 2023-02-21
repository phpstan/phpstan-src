<?php // lint >= 8.0

namespace Bug8932;

class HelloWorld
{
	/**
	 * @param 'A'|'B' $string
	 */
	public function sayHello(string $string): int
	{
		return match ($string) {
			'A' => 1,
			'B' => 2,
			default => throw new \LogicException(),
		};
	}
}
