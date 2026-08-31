<?php declare(strict_types = 1);

namespace Bug15145;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type config array{"string1":array{one:int},"string2":array{two:int}}
 */
class SomeClass
{

	/**
	 * @template TKey of string
	 * @param TKey $key
	 *
	 * @return array{TKey: config[TKey]}
	 */
	public function config(string $key): array
	{
		throw new \Exception();
	}

}

function test(SomeClass $c, string $s): void
{
	assertType('array{string1: array{one: int}}', $c->config('string1'));
	assertType('array{string2: array{two: int}}', $c->config('string2'));
	assertType('non-empty-array<string, array{one: int}|array{two: int}>', $c->config($s));
}
