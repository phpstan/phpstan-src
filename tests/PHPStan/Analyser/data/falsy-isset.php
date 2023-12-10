<?php

namespace FalsyIsset;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

class ArrayOffset
{
	public function undefinedVar(): void
	{
		if (isset($a['bar'])) {
			assertType("*ERROR*", $a);
		} else {
			assertType("*ERROR*", $a);
		}
	}

	public function definedVar($a): void
	{
		if (isset($a['bar'])) {
			assertType("mixed~null", $a);
		} else {
			assertType("mixed", $a);
		}
	}

	/**
	 * @param array{bar?: null}|array{bar?: 'hello'} $a
	 */
	public function optionalOffsetNull($a): void
	{
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType('array{bar?: null}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}|array{bar?: null}", $a);
	}

	/**
	 * @param array{bar?: 'world'}|array{bar?: 'hello'} $a
	 */
	public function optionalOffsetNonNull($a): void
	{
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType('array{}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{}|array{bar: 1}", $a);
	}

	public function maybeCertainNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => null];
			if (rand() % 3) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: 'hello'}|array{bar: null}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
			assertType('array{bar: null}', $a);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertType("array{bar: 1}|array{bar: null}", $a);
	}

	public function maybeCertainNonNull(): void
	{
		if (rand() % 2) {
			$a = ['bar' => 'world'];
			if (rand() % 3) {
				$a = ['bar' => 'hello'];
			}
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $a);
			assertType('*ERROR*', $a);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertType("array{bar: 1}", $a);
	}

	public function maybeCertainNonNullMultiOffsetShape(): void
	{
		if (rand() % 2) {
			$a = [
				'bar' => 'world',
				'foo' => 'hello'
			];
		}
		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);

		assertType("array{bar: 'world', foo: 'hello'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'world', foo: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1, foo: 'hello'}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $a);
			assertType("*ERROR*", $a);
		}

		assertVariableCertainty(TrinaryLogic::createMaybe(), $a);
		assertType("array{bar: 1, foo: 'hello'}", $a);
	}

	public function yesCertainNull(): void
	{
		$a = ['bar' => null];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: 'hello'}|array{bar: null}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: null}", $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}|array{bar: null}", $a);
	}

	public function yesCertainNonNull(): void
	{
		$a = ['bar' => 'world'];
		if (rand() % 2) {
			$a = ['bar' => 'hello'];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
		if (isset($a['bar'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: 'hello'}|array{bar: 'world'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createNo(), $a);
			assertType('*ERROR*', $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: 1}", $a);
	}

	public function nestedFetch(): void
	{
		$a = ['bar' => null];
		if (rand() % 2) {
			$a = ['bar' => ['foo' => 'hello']];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: array{foo: 'hello'}}|array{bar: null}", $a);
		if (isset($a['bar']['foo'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: array{foo: 'hello'}}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: null}", $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: array{foo: 'hello'}}|array{bar: null}", $a);
	}

	public function nestedNullableFetch(?string $nullableString): void
	{
		$a = ['bar' => null];
		if (rand() % 2) {
			$a = ['bar' => ['foo' => $nullableString]];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{bar: array{foo: string|null}}|array{bar: null}", $a);
		if (isset($a['bar']['foo'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: array{foo: string}}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: array{foo: null}}|array{bar: null}", $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: array{foo: null}}|array{bar: array{foo: string}}|array{bar: null}", $a);
	}

	public function nestedOptionalNullableFetch(?string $nullableString): void
	{
		$a = [];
		if (rand() % 2) {
			$a = ['bar' => ['foo' => $nullableString]];
		}
		assertVariableCertainty(TrinaryLogic::createYes(), $a);

		assertType("array{}|array{bar: array{foo: string|null}}", $a);
		if (isset($a['bar']['foo'])) {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: array{foo: string}}", $a);
		} else {
			assertVariableCertainty(TrinaryLogic::createYes(), $a);
			assertType("array{bar: array{foo: null}}", $a);
		}

		assertVariableCertainty(TrinaryLogic::createYes(), $a);
		assertType("array{bar: array{foo: null}}|array{bar: array{foo: string}}", $a);
	}

}

function doFoo():mixed {
	return 1;
}

function maybeMixedVariable(): void
{
	if (rand(0,1)) {
		$a = doFoo();
	}

	if (isset($a)) {
		assertType("mixed~null", $a);
	} else {
		assertType("null", $a);
	}
}

function maybeNullableVariable(): void
{
	if (rand(0,1)) {
		$a = 'hello';

		if (rand(0,2)) {
			$a = null;
		}
	}

	if (isset($a)) {
		assertType("'hello'", $a);
	} else {
		assertType("null", $a);
	}
}

function subtractedMixedIsset(mixed $m): void
{
	if ($m === null) {
		return;
	}

	assertType("mixed~null", $m);
	if (isset($m)) {
		assertType("mixed~null", $m);
	} else {
		assertType("*ERROR*", $m);
	}
}

function mixedIsset(mixed $m): void
{
	if (isset($m)) {
		assertType("mixed~null", $m);
	} else {
		assertType("null", $m);
	}
}

function stdclassIsset(?\stdClass $m): void
{
	if (isset($m)) {
		assertType("stdClass", $m);
	} else {
		assertType("null", $m);
	}
}

function maybeNonNullableVariable(): void
{
	if (rand(0,1)) {
		$a = 'hello';
	}

	if (isset($a)) {
		assertType("'hello'", $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $a);
		assertType("*ERROR*", $a);
	}
}

function nonNullableVariable(string $a): void
{
	if (isset($a)) {
		assertType("string", $a);
	} else {
		assertVariableCertainty(TrinaryLogic::createNo(), $a);
		assertType("*ERROR*", $a);
	}
}

function nullableVariable(?string $a): void
{
	if (isset($a)) {
		assertType("string", $a);
	} else {
		assertType("null", $a);
	}
}

function nullableUnionVariable(null|string|int $a): void
{
	if (isset($a)) {
		assertType("int|string", $a);
	} else {
		assertType("null", $a);
	}
}

function render(?int $noteListLimit, int $count): void
{
	$showAllLink = $noteListLimit !== null && $count > $noteListLimit;
	if ($showAllLink) {
		assertType('int', $noteListLimit);
	}
}

/**
 * @param mixed[] $requestAttributes
 */
function getParameters(string $legacyLink, array $requestAttributes): void
{
	$legacyParameters = [];

	assertVariableCertainty(\PHPStan\TrinaryLogic::createYes(), $legacyParameters);
	if (isset($requestAttributes['_legacy_link'])) {
		$linkParts = explode(':', $legacyLink);
		if (!isset($legacyParameters['controller'])) {
			$legacyParameters['controller'] = $linkParts[0];
		}

		assertVariableCertainty(\PHPStan\TrinaryLogic::createYes(), $legacyParameters);
		if (isset($legacyParameters['controller'], $legacyParameters['action'])) {
		}
		assertVariableCertainty(\PHPStan\TrinaryLogic::createYes(), $legacyParameters);
	}
}
