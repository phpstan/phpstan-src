<?php

namespace GenericCallables;

use Closure;

use function PHPStan\Testing\assertType;

/**
 * @template TFuncRet of mixed
 * @param TFuncRet $mixed
 *
 * @return Closure(): TFuncRet
 */
function testFuncClosure(mixed $mixed): Closure
{
}

/**
 * @template TFuncRet of mixed
 * @param TFuncRet $mixed
 *
 * @return Closure<TClosureRet of mixed>(TClosureRet $val): (TClosureRet|TFuncRet)
 */
function testFuncClosureMixed(mixed $mixed)
{
}

/**
 * @template TFuncRet of mixed
 * @param TFuncRet $mixed
 *
 * @return callable(): TFuncRet
 */
function testFuncCallable(mixed $mixed): callable
{
}

/**
 * @param Closure<TRet of mixed>(TRet $val): TRet $callable
 * @param non-empty-list<Closure<TRet of mixed>(TRet $val): TRet> $callables
 */
function testClosure(Closure $callable, int $int, string $str, array $callables): void
{
	assertType('Closure<TRet of mixed>(TRet): TRet', $callable);
	assertType('int', $callable($int));
	assertType('string', $callable($str));
	assertType('string', $callables[0]($str));
	assertType('Closure(): 1', testFuncClosure(1));
}

function testClosureMixed(int $int, string $str): void
{
	$closure = testFuncClosureMixed($int);
	assertType('Closure<TClosureRet of mixed>(TClosureRet): (int|TClosureRet)', $closure);
	assertType('int|string', $closure($str));
}

/**
 * @param callable<TRet of mixed>(TRet $val): TRet $callable
 */
function testCallable(callable $callable, int $int, string $str): void
{
	assertType('callable<TRet of mixed>(TRet): TRet', $callable);
	assertType('int', $callable($int));
	assertType('string', $callable($str));
	assertType('callable(): 1', testFuncCallable(1));
}

/**
 * @param Closure<TRetFirst of mixed>(TRetFirst $valone): (Closure<TRetSecond of mixed>(TRetSecond $valtwo): (TRetFirst|TRetSecond)) $closure
 */
function testNestedClosures(Closure $closure, string $str, int $int): void
{
	assertType('Closure<TRetFirst of mixed>(TRetFirst): (Closure<TRetSecond of mixed>(TRetSecond $valtwo): (TRetFirst|TRetSecond))', $closure);
	$closure1 = $closure($str);
	assertType('Closure<TRetSecond of mixed>(TRetSecond): (string|TRetSecond)', $closure1);
	$result = $closure1($int);
	assertType('int|string', $result);
}

/**
 * @template T
 * @param T $arg
 * @return T
 */
function foo(mixed $arg): mixed {}

class Foo
{
	/**
	 * @template T
	 * @param T $arg
	 * @return T
	 */
	public function foo(mixed $arg): mixed {}
}

function test(): void
{
	assertType('Closure<T of mixed>(T): T', foo(...));
	assertType('1', foo(...)(1));

	$foo = new Foo();
	$closure = Closure::fromCallable([$foo, 'foo']);
	assertType('Closure<T of mixed>(T): T', $closure);
	assertType('1', $closure(1));
}

/**
 * @template A
 * @param A $value
 * @return A
 */
function identity(mixed $value): mixed
{
	return $value;
}

/**
 * @template B
 * @param B $value
 * @return B
 */
function identity2(mixed $value): mixed
{
	return $value;
}

function testIdentity(): void
{
	assertType('array{1, 2, 3}', array_map(identity(...), [1, 2, 3]));
}

/**
 * @template A
 * @template B
 * @param A $value
 * @param B $value2
 * @return A|B
 */
function identityTwoArgs(mixed $value, mixed $value2): mixed
{
	return $value || $value2;
}

function testIdentityTwoArgs(): void
{
	assertType('non-empty-array<int, 1|2|3|4|5|6>', array_map(identityTwoArgs(...), [1, 2, 3], [4, 5, 6]));
}

/**
 * @template A
 * @template B
 * @param list<A> $a
 * @param list<B> $b
 * @return list<array{A, B}>
 */
function zip(array $a, array $b): array
{
}

function testZip(): void
{
	$fn = zip(...);

	assertType('list<array{1, 2}>', $fn([1], [2]));
}

/**
 * @template X
 * @template Y
 * @template Z
 * @param callable(X, Y): Z $fn
 * @return callable(Y, X): Z
 */
function flip(callable $fn): callable
{
}

/**
 * @param Closure<A of string, B of int>(A $a, B $b): (A|B) $closure
 */
function testFlip($closure): void
{
	$fn = flip($closure);

	assertType('callable(B, A): (A|B)', $fn);
	assertType("1|'one'", $fn(1, 'one'));
}

function testFlipZip(): void
{
	$fn = flip(zip(...));

	assertType('list<array{2, 1}>', $fn([1], [2]));
}

/**
 * @template L
 * @template M
 * @template N
 * @template O
 * @param callable(L): M $ab
 * @param callable(N): O $cd
 * @return Closure(array{L, N}): array{M, O}
 */
function compose2(callable $ab, callable $cd): Closure
{
	throw new \RuntimeException();
}

function testCompose(): void
{
	$composed = compose2(
		identity(...),
		identity2(...),
	);

	assertType('Closure(array{A, B}): array{A, B}', $composed);

	assertType('array{1, 2}', $composed([1, 2]));
}
