<?php // lint >= 8.6

namespace PartialFunctionApplicationBoundArgs;

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

/**
 * @return list<\Closure>
 */
function readInBoundArguments(Foo $foo, callable $cb, string $name): array
{
	$a = 'a';
	$b = 'b';
	$c = 'c';
	$d = 'd';
	$e = 'e';
	$unused = 'unused';

	return [f($a, ?), $foo->m($b, ?), Foo::s(?, $c), $cb($d, ?), $foo->$name($e, ?)];
}

function receiverIsReadBeforeBoundArguments(): \Closure
{
	$foo = new Foo();

	return $foo->m(($foo = new Foo()) instanceof Foo ? 'a' : 'b', ?);
}

function terminatingFunction(): void
{
	f(exit(), ?);
	echo 'unreachable';
}

function terminatingMethod(Foo $foo): void
{
	$foo->m(exit(), ?);
	echo 'unreachable';
}

function terminatingStaticMethod(): void
{
	Foo::s(?, exit());
	echo 'unreachable';
}

function terminatingDynamicCallable(callable $cb): void
{
	$cb(exit(), ?);
	echo 'unreachable';
}

function notTerminating(Foo $foo): void
{
	f('a', ?);
	$foo->m('a', ?);
	Foo::s(?, 'a');
	echo 'reachable';
}
