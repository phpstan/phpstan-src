<?php // lint >= 8.6

namespace PartialFunctionApplicationPure;

class Foo
{

	/** @phpstan-pure */
	public function m(string $a, string $b): string
	{
		return $a . $b;
	}

	/** @phpstan-pure */
	public static function s(string $a, string $b): string
	{
		return $a . $b;
	}

}

/** @phpstan-pure */
function f(string $a, string $b): string
{
	return $a . $b;
}

/**
 * @phpstan-pure
 * @return list<\Closure>
 */
function pure(Foo $foo): array
{
	return [
		f((string) rand(), ?),
		$foo->m((string) rand(), ?),
		Foo::s(?, (string) rand()),
		f('a', ?),
		$foo->m('a', ?),
		Foo::s(?, 'a'),
	];
}
