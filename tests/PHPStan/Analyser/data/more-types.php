<?php

namespace MoreTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param pure-callable $pureCallable
	 * @param callable-array $callableArray
	 * @param closed-resource $closedResource
	 * @param open-resource $openResource
	 * @param enum-string $enumString
	 * @param non-empty-literal-string $nonEmptyLiteralString
	 * @param non-empty-scalar $nonEmptyScalar
	 * @param empty-scalar $emptyScalar
	 * @param non-empty-mixed $nonEmptyMixed
	 */
	public function doFoo(
		$pureCallable,
		$callableArray,
		$closedResource,
		$openResource,
		$enumString,
		$nonEmptyLiteralString,
		$nonEmptyScalar,
		$emptyScalar,
		$nonEmptyMixed
	): void
	{
		assertType('pure-callable(): mixed', $pureCallable);
		assertType('array&callable(): mixed', $callableArray);
		assertType('resource', $closedResource);
		assertType('resource', $openResource);
		assertType('class-string', $enumString);
		assertType('literal-string&non-empty-string', $nonEmptyLiteralString);
		assertType('float|int<min, -1>|int<1, max>|non-falsy-string|true', $nonEmptyScalar);
		assertType("0|0.0|''|'0'|false", $emptyScalar);
		assertType("mixed~0|0.0|''|'0'|array{}|false|null", $nonEmptyMixed);
	}

}
