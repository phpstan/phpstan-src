<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug8204;

function f(string ...$parameters) : void {
}

class HelloWorld
{
    private const FOO = 'foo';
    private string $bar = 'bar';

	public function foobar(): void
	{
        f(
            foo: self::FOO,
            bar: $this->bar,
        );
	}
}
