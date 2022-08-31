<?php

declare(strict_types=1);

namespace ParseStr;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param non-empty-string $nonEmptyString
	 * @param numeric-string $numericString
	 * @param 'foo=bar'|'baz[]=qux'|'foo=baz' $constantStrings
	 */
	public function bar(
		string $ordinaryString,
		string $nonEmptyString,
		string $numericString,
		string $constantStrings
	): void {

		parse_str($ordinaryString, $result);
		assertType('non-empty-array<int|non-empty-string, mixed>', $result);

		parse_str($nonEmptyString, $result);
		assertType('non-empty-array<int|non-empty-string, mixed>', $result);

		parse_str($numericString, $result);
		assertType("non-empty-array<int|non-empty-string, ''>", $result);

		parse_str($constantStrings, $result);
		assertType("array{baz: array{'qux'}}|array{foo: 'bar'}|array{foo: 'baz'}", $result);

		parse_str('', $result);
		assertType('array{}', $result);

		parse_str('foo=bar', $result);
		assertType("array{foo: 'bar'}", $result);
	}
}
