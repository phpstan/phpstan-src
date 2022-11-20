<?php declare(strict_types=1);

namespace Superglobals;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Superglobals
{

	public function originalTypes(): void
	{
		assertType('array<string, mixed>', $GLOBALS);
		assertType('array<string, mixed>', $_SERVER);
		assertType('array<string, mixed>', $_GET);
		assertType('array<string, mixed>', $_POST);
		assertType('array<string, mixed>', $_FILES);
		assertType('array<string, mixed>', $_COOKIE);
		assertType('array<string, mixed>', $_SESSION);
		assertType('array<string, mixed>', $_REQUEST);
		assertType('array<string, mixed>', $_ENV);
	}

	public function canBeOverwritten(): void
	{
		$GLOBALS = [];
		assertType('array{}', $GLOBALS);
		assertNativeType('array{}', $GLOBALS);
	}

	public function canBePartlyOverwritten(): void
	{
		$GLOBALS['foo'] = 'foo';
		assertType("non-empty-array<string, mixed>&hasOffsetValue('foo', 'foo')", $GLOBALS);
		assertNativeType("non-empty-array<string, mixed>&hasOffsetValue('foo', 'foo')", $GLOBALS);
	}

	public function canBeNarrowed(): void
	{
		if (isset($GLOBALS['foo'])) {
			assertType("array<string, mixed>&hasOffsetValue('foo', mixed~null)", $GLOBALS);
			assertNativeType("array<string, mixed>&hasOffset('foo')", $GLOBALS); // https://github.com/phpstan/phpstan/issues/8395
		} else {
			assertType('array<string, mixed>', $GLOBALS);
			assertNativeType('array<string, mixed>', $GLOBALS);
		}
		assertType('array<string, mixed>', $GLOBALS);
		assertNativeType('array<string, mixed>', $GLOBALS);
	}

}

function functionScope() {
	assertType('array<string, mixed>', $GLOBALS);
	assertNativeType('array<string, mixed>', $GLOBALS);
}

assertType('array<string, mixed>', $GLOBALS);
assertNativeType('array<string, mixed>', $GLOBALS);
