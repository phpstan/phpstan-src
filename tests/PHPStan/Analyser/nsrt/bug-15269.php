<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15269;

use Closure;
use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template TColumn of string
 * @param array<TColumn, \Closure(TColumn): string> $formatters
 */
function formatColumns(array $formatters): void
{
}

formatColumns([
	'name' => function ($column) {
		assertType("'email'|'name'", $column);
		return strtoupper($column);
	},
	'email' => fn ($column) => assertType("'email'|'name'", $column),
]);

/**
 * @template T
 * @param array{0: T, 1: \Closure(T): void} $a
 */
function siblingValue(array $a): void
{
}

siblingValue([1, function ($x) {
	assertType('1', $x);
}]);

/**
 * @template T
 * @param array{0: \Closure(T): void, 1: T} $a
 */
function siblingValueReversed(array $a): void
{
}

siblingValueReversed([function ($x) {
	assertType("'foo'", $x);
}, 'foo']);

/**
 * @template T of string
 * @param array<string, array<T, \Closure(T): string>> $f
 */
function nested(array $f): void
{
}

nested(['group' => ['inner' => function ($x) {
	assertType("'inner'", $x);
	return 'z';
}]]);

/**
 * @template T of string
 * @param array<T, callable(T): string> $f
 */
function withCallable(array $f): void
{
}

withCallable(['e' => function ($x) {
	assertType("'e'", $x);
	return 'z';
}]);

/**
 * @template T of object
 * @param array<class-string<T>, \Closure(T): void> $handlers
 */
function handlers(array $handlers): void
{
}

handlers([stdClass::class => function ($o) {
	assertType('stdClass', $o);
}]);

/**
 * @template T of string
 * @param array<T, \Closure(T): string> ...$f
 */
function variadic(array ...$f): void
{
}

variadic(['v' => function ($x) {
	assertType("'v'", $x);
	return 'z';
}]);

/**
 * @template T of string
 * @param array<T, \Closure(T): string> $f
 */
function named(array $f): void
{
}

named(f: ['n' => fn ($x) => assertType("'n'", $x)]);

/**
 * @template T of string
 * @param array{0: T, 1: \Closure(T): void} $a
 */
function boundedUnknown(array $a): void
{
}

function unknownString(): string
{
	return 'x';
}

// the first slot cannot be priced without a walk, so the template stays at its
// bound instead of being widened to mixed
boundedUnknown([unknownString(), function ($x) {
	assertType('string', $x);
}]);

class Svc
{

	/**
	 * @template T of string
	 * @param array<T, \Closure(T): string> $f
	 */
	public function __construct(array $f)
	{
	}

	/**
	 * @template T of string
	 * @param array<T, \Closure(T): string> $f
	 */
	public function method(array $f): void
	{
	}

	/**
	 * @template T of string
	 * @param array<T, \Closure(T): string> $f
	 */
	public static function staticMethod(array $f): void
	{
	}

}

$svc = new Svc(['a' => function ($x) {
	assertType("'a'", $x);
	return 'z';
}]);
$svc->method(['b' => function ($x) {
	assertType("'b'", $x);
	return 'z';
}]);
Svc::staticMethod(['c' => function ($x) {
	assertType("'c'", $x);
	return 'z';
}]);

/**
 * @template T
 * @template U
 * @param array{\Closure(T): U, T} $a
 * @return U
 */
function inferReturn(array $a)
{
	throw new \Exception();
}

assertType("'3'", inferReturn([fn ($x) => (string) $x, 3]));
