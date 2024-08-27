<?php // lint >= 8.0

namespace NoNamedArgumentsFunction;

/**
 * @no-named-arguments
 */
function foo(int $i): void
{

}

function (): void {
	foo(i: 5);
};

/**
 * @param array<string, int> $a
 * @param array<int|string, int> $b
 * @param array<int, int> $c
 */
function bar(array $a, array $b, array $c): void
{
	foo(...$a);
	foo(...$b);
	foo(...$c);

	foo(...[0 => 1]);
	foo(...['i' => 1]);
}
