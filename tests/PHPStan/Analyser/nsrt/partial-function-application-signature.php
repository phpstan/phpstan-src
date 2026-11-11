<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationSignature;

use function PHPStan\Testing\assertType;

function doFoo(int $a, string $b, float $c, bool ...$d): string
{
	return '';
}

function opt(int $a, string $b = 'B', float $c = 1.0): int
{
	return 1;
}

function none(): void
{
}

function byRef(int &$a, string $b): void
{
}

function withNullableDefault(?int $a = null, string ...$rest): void
{
}

/**
 * @template T
 * @param T $a
 * @return T
 */
function identity($a)
{
	return $a;
}

/**
 * @template T
 * @param T $value
 * @param T $other
 * @return list<T>
 */
function pair($value, $other): array
{
	return [$value, $other];
}

/**
 * @template T
 * @param T $value
 * @return list<T>
 */
function repeat($value, int $times): array
{
	return [];
}

/**
 * @template T
 * @template U
 * @param list<T> $items
 * @param callable(T): U $fn
 * @return list<U>
 */
function mapList(array $items, callable $fn): array
{
	return [];
}

class Foo
{

	public function doFoo(int $a, string $b, float ...$c): bool
	{
		return true;
	}

	public static function doStatic(int $a, string $b = 'x'): int
	{
		return 1;
	}

}

class Invokable
{

	public function __invoke(int $a, string $b): bool
	{
		return true;
	}

}

class Constants
{

	const PARTIAL = doFoo(1, ?, ...);

	const REPEATED = repeat('a', ?);

}

function functions(): void
{
	// the cases checked against PHP 8.6
	assertType('static-Closure(float, bool ...): string', doFoo(1, 'b', ...));
	assertType('static-Closure(string, float, bool ...): string', doFoo(1, ?, ...));
	assertType('static-Closure(string, bool ...): string', doFoo(1, ?, 3.0, ...));
	assertType('static-Closure(string, bool ...): string', doFoo(1, ?, 3.0, true, ...));
	assertType('static-Closure(bool ...): string', doFoo(1, 'b', 3.0, ...));
	assertType('static-Closure(string): string', doFoo(1, ?, 3.0));
	assertType('static-Closure(string): string', doFoo(1, ?, 3.0, true));
	assertType('static-Closure(bool): string', doFoo(1, 'b', 3.0, true, ?));
	assertType('static-Closure(bool, bool): string', doFoo(1, 'b', 3.0, ?, ?));
	assertType('static-Closure(bool, bool ...): string', doFoo(1, 'b', 3.0, ?, ...));
	assertType('static-Closure(bool ...): string', doFoo(1, 'b', 3.0, x: true, ...));

	// a placeholder is required even for an optional parameter, ... keeps the optionality
	assertType('static-Closure(int): int', opt(?));
	assertType('static-Closure(int, string): int', opt(?, ?));
	assertType('static-Closure(string=, float=): int', opt(1, ...));
	assertType('static-Closure(): int', opt(1, 'b', 2.0, ...));
	assertType('static-Closure(int|null, string ...): void', withNullableDefault(?, ...));

	// named placeholders come after the positional ones, in the order they are written,
	// and before what ... adds
	assertType('static-Closure(float, int, string): string', doFoo(c: ?, a: ?, b: ?));
	assertType('static-Closure(int, float, string, bool ...): string', doFoo(?, c: ?, ...));
	assertType('static-Closure(string, int, float=): int', opt(b: ?, ...));
	assertType('static-Closure(int): int', opt(?, c: 2.0));

	// by-ref is kept
	assertType('static-Closure(int, string): void', byRef(?, ?));

	// the partial application throws when it is created
	assertType('Closure', doFoo(1, ?));
	assertType('Closure', none(?));
	assertType('Closure', none(1, ...));
	assertType('Closure', opt(1, 'b', 2.0, ?));
	assertType('Closure', opt(x: ?));
	assertType('Closure', doFoo(1, 'b', 3.0, d: ?));
	assertType('Closure', opt(?, a: 1));
	assertType('Closure', opt(a: ?, a: ?));
	assertType('Closure', doFoo(?, ..., ...));

	// built-ins
	assertType('static-Closure(string): (non-empty-string|false)', strtok(?));
	assertType('static-Closure(string, string): (non-empty-string|false)', strtok(?, ?));
	assertType('static-Closure(string): int<0, max>', strlen(?));
	assertType('Closure', mt_rand(?));

	// templates
	assertType('static-Closure<T of mixed>(T): T', identity(?));
	assertType('static-Closure(int): list<\'a\'>', repeat('a', ?));
	assertType('static-Closure<T of mixed>(T): list<T>', repeat(?, 3));
	// a template a closure parameter still mentions gets what the bound arguments bring to it
	assertType('static-Closure<T of mixed>(T): list<\'x\'|T>', pair(?, 'x'));
	$pair = pair(?, 'x');
	assertType('list<1|\'x\'>', $pair(1));
}

/**
 * @param list<int> $ints
 */
function templatesThroughCallbacks(array $ints): void
{
	assertType('list<list<\'x\'|int>>', array_map(pair(?, 'x'), $ints));
	assertType('list<list<int>>', array_map(repeat(?, 2), $ints));
	assertType('list<list<\'x\'|int>>', mapList($ints, pair(?, 'x')));
	assertType('list<list<int>>', mapList($ints, repeat(?, 2)));
	assertType('list<int>', mapList($ints, identity(...)));
	assertType('list<int>', mapList($ints, identity(?)));
}

function methods(Foo $foo): void
{
	assertType('Closure(string): bool', $foo->doFoo(1, ?));
	assertType('Closure(float, float): bool', $foo->doFoo(1, 'b', ?, ?));
	assertType('Closure(string, float ...): bool', $foo->doFoo(1, ...));
	assertType('Closure', $foo->doFoo(?));

	assertType('static-Closure(int): int', Foo::doStatic(?));
	assertType('static-Closure(int, string=): int', Foo::doStatic(?, ...));
	assertType('static-Closure(string): int', Foo::doStatic(1, ?));
	assertType('Closure', Foo::doStatic(1, 'b', ?));
}

function closures(Invokable $i): void
{
	assertType('Closure(string): bool', $i(1, ?));
	assertType('Closure(int): bool', $i->__invoke(?, 'x'));
	assertType('static-Closure(string, float, bool ...): string', Constants::PARTIAL);
	assertType('static-Closure(int): list<\'a\'>', Constants::REPEATED);

	$c = fn (int $a, string $b, float $c = 1.0): int => 1;
	assertType('Closure(string): 1', $c(1, ?));
	assertType('Closure(string): 1', $c->__invoke(1, ?));
	assertType('Closure(string, float=): 1', $c(1, ...));
	assertType('Closure', $c(?));

	$d = doFoo(1, ?, ...);
	assertType('static-Closure(string): string', $d(?, 3.0));
	assertType('static-Closure(bool): string', $d('b', 3.0, ?));
}

function bind(Foo $foo, Invokable $i, object $o): void
{
	assertType('null', doFoo(1, ?, ...)->bindTo($o));
	assertType('null', strlen(?)->bindTo($o));
	assertType('static-Closure(string): string', doFoo(1, ?, 3.0)->bindTo(null));
	assertType('null', Foo::doStatic(1, ?)->bindTo($o));
	assertType('((Closure(string, float ...): bool)|null)', $foo->doFoo(1, ...)->bindTo($o));
	assertType('null', $foo->doFoo(1, ...)->bindTo(null));
	assertType('((Closure(string): bool)|null)', $i(1, ?)->bindTo($o));
}
