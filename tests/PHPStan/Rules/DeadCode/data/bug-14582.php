<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug14582;

use Closure;
use RuntimeException;

/**
 * @template TLeft
 * @template TRight
 */
interface Choice
{
	/**
	 * @template TResult
	 *
	 * @param (Closure(TRight): TResult) $right
	 * @param-immediately-invoked-callable $right
	 * @param (Closure(TLeft): TResult) $left
	 * @param-immediately-invoked-callable $left
	 *
	 * @return TResult
	 */
	public function proceed(Closure $right, Closure $left): mixed;
}

/** @return Choice<string, null> */
function getChoice(bool $right): Choice
{
	throw new RuntimeException('stub');
}

function noop(): void
{
}

function doSomethingAfterwards(): void
{
}

function test(bool $right): void
{
	getChoice($right)->proceed(
		right: noop(...),
		left: fn (string $message) => throw new RuntimeException($message),
	);
	doSomethingAfterwards();
}

function testArrayFilter(): void
{
	$b = array_filter([], fn() => throw new \Error());
	echo $b;
}

function testArrayMap(): void
{
	array_map(fn() => throw new \Error(), []);
	echo 'reachable';
}

function testUsort(): void
{
	$a = [1, 2, 3];
	usort($a, fn($a, $b) => throw new \Error());
	echo 'reachable';
}

function testClosureArgument(): void
{
	array_filter([], function () {
		throw new \Error();
	});
	echo 'reachable';
}
