<?php // lint >= 8.0

declare(strict_types = 1);

namespace ArrayClosureParameters;

use Closure;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

/** @param list<callable(string, int): mixed> $callbacks */
function callbacks(array $callbacks): void {}

callbacks([
	function ($value, $key) {
		assertType('string', $value);
		assertType('int', $key);
		assertNativeType('mixed', $value);
		assertNativeType('mixed', $key);
		return $value;
	},
	fn ($value, $key) => [assertType('string', $value), assertType('int', $key), assertNativeType('mixed', $value)],
	function (int $value, $key) {
		assertType('int', $value);
		assertType('int', $key);
	},
]);

/** @param array{first: callable(string): mixed, second?: Closure(int): mixed} $callbacks */
function shape(array $callbacks): void {}

shape(callbacks: [
	'second' => fn ($value) => assertType('int', $value),
	'first' => function ($value) {
		assertType('string', $value);
		$unrelated = [function ($other) {
			assertType('mixed', $other);
		}];
	},
]);

/** @param array{callable(string): mixed, callable(int): mixed} $callbacks */
function tuple(array $callbacks): void {}

tuple([
	fn ($value) => assertType('string', $value),
	fn ($value) => assertType('int', $value),
]);

/** @param array{5: string, 6: callable(int): mixed} $callbacks */
function numericShape(array $callbacks): void {}

numericShape([5 => 'value', fn ($value) => assertType('int', $value)]);

/** @param array<string, list<callable(string): mixed>> $callbacks */
function nested(array $callbacks): void {}

nested(['first' => [function ($value) {
	assertType('string', $value);
}]]);

/** @param list<callable(string): mixed>|null $callbacks */
function nullable(?array $callbacks): void {}

nullable([fn ($value) => assertType('string', $value)]);

/** @param iterable<callable(string): mixed> $callbacks */
function iterableCallbacks(iterable $callbacks): void {}

iterableCallbacks([fn ($value) => assertType('string', $value)]);

/**
 * @template T
 * @param T $value
 * @param list<callable(T): mixed> $callbacks
 */
function generic($value, array $callbacks): void {}

generic(new \stdClass(), [fn ($value) => assertType('stdClass', $value)]);

class Receiver
{
	/** @param list<callable(string): mixed> $callbacks */
	public function __construct(array $callbacks) {}

	/** @param list<callable(int): mixed> $callbacks */
	public static function run(array $callbacks): void {}

	/** @param list<callable(string): mixed> ...$callbacks */
	public function variadic(array ...$callbacks): void {}
}

$receiver = new Receiver([fn ($value) => assertType('string', $value)]);
Receiver::run([fn ($value) => assertType('int', $value)]);
$receiver->variadic([fn ($value) => assertType('string', $value)], [fn ($value) => assertType('string', $value)]);

$unrelated = [fn ($value) => assertType('mixed', $value)];
callbacks($unrelated);
