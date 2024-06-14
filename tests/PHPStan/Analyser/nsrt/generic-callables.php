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
