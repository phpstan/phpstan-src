<?php declare(strict_types=1);

namespace Superglobals;

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
		$_SESSION = [];
		assertType('array{}', $_SESSION);
	}

	public function canBePartlyOverwritten(): void
	{
		$_SESSION['foo'] = 'foo';
		assertType("non-empty-array<string, mixed>&hasOffsetValue('foo', 'foo')", $_SESSION);
	}

}
