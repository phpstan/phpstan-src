<?php declare(strict_types = 1);

namespace PHPStanTurboTests;

/**
 * The signatures the parameter reflection and function variant
 * differentials in type-family.php reflect (through the container's
 * reflection provider): parameters without and with native and PHPDoc
 * types, null and non-null defaults, by-reference, variadic, out,
 * closure-this, immediately-invoked and pure-unless-callable-is-impure
 * parameters, templates and conditional return types. The bodies are never
 * run.
 *
 * @template TClass
 */
class SignatureFixture
{

	/**
	 * @param int $untyped
	 * @param string|null $nullDefault
	 * @param positive-int $intDefault
	 * @param list<string> $arrayDefault
	 */
	public function defaults($untyped, ?string $nullDefault = null, int $intDefault = 5, array $arrayDefault = [], $noTypeNoDefault = null): void
	{
	}

	/**
	 * @param array<int> $matches
	 * @param-out array<string> $matches
	 * @param int ...$rest
	 */
	public function byRefAndVariadic(array &$matches, int|string $union, int ...$rest): int
	{
		return 0;
	}

	/**
	 * @param callable(int): string $callback
	 * @param-immediately-invoked-callable $callback
	 * @param-closure-this \stdClass $later
	 * @pure-unless-callable-is-impure $callback
	 */
	public function callables(callable $callback, \Closure $later): string
	{
		return '';
	}

	/**
	 * @template T of int|string
	 * @param T $value
	 * @param TClass $classValue
	 * @return ($value is int ? list<T> : array<string, T>)
	 */
	public function templated(int|string $value, mixed $classValue = null): array
	{
		return [];
	}

	/**
	 * @phpstan-assert-if-true int $value
	 * @phpstan-assert !null $other
	 */
	public static function asserting(mixed $value, mixed $other, self|null $self = null): bool
	{
		return true;
	}

}

/**
 * @template T
 * @param T $value
 * @param-out T $out
 * @return list<T>
 */
function signatureFixtureFunction(mixed $value, mixed &$out = null, string $name = 'x'): array
{
	return [];
}
