<?php // lint >= 8.6

namespace PartialFunctionApplicationBoundArgsCalls;

class Foo
{

	public function m(string $a, string $b): string
	{
		return $a . $b;
	}

	public static function s(string $a, string $b): string
	{
		return $a . $b;
	}

}

function f(string $a, string $b): string
{
	return $a . $b;
}

function (Foo $foo, callable $cb): void {
	f((string) rand(), ?);
	$foo->m((string) rand(), ?);
	Foo::s(?, (string) rand());
	$cb((string) rand(), ?);

	f('a', ?);
	$foo->m('a', ?);
	Foo::s(?, 'a');
};

/**
 * @return list<\Closure>
 */
function nestedCalls(Foo $foo, callable $cb): array
{
	return [
		f((string) strlen([]), ?),
		$foo->m((string) strlen([]), ?),
		Foo::s(?, (string) strlen([])),
		$cb((string) strlen([]), ?),
	];
}
