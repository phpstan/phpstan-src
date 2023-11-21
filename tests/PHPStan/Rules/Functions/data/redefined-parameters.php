<?php declare(strict_types = 1);

namespace RedefinedParameters;

class HelloWorld
{

    /**
     * @return \Closure(string, int): int
     */
	public function foo(string $foo, string $foo): \Closure
	{
        $callback = static fn (int $bar, array $bar): int => (int) $bar;

        return function (string $baz, int $baz) use ($callback): int {
            return $callback($baz, []);
        };
	}

    /**
     * @return \Closure(string, bool): int
     */
    public function bar(string $pipe, int $count): \Closure
    {
        $cb = fn (int $a, string $b): int => $a + (int) $b;

        return function (string $c, bool $d) use ($cb, $pipe, $count): int {
            return $cb((int) $d, $c) + $cb($count, $pipe);
        };
    }

}
