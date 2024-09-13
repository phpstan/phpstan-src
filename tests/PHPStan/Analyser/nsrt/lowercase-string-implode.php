<?php

namespace LowercaseStringImplode;

use function htmlspecialchars;
use function lcfirst;
use function PHPStan\Testing\assertType;
use function strtolower;
use function strtoupper;
use function ucfirst;

class ExplodingStrings
{
	public function doFoo(string $s): void
	{
		assertType('non-empty-list<lowercase-string>', explode($s, 'foo'));
		assertType('non-empty-list<string>', explode($s, 'FOO'));
	}
}

class ImplodingStrings
{

	/**
	 * @param lowercase-string $ls
	 * @param array<string> $commonStrings
	 * @param array<lowercase-string> $lowercaseStrings
	 */
	public function doFoo(string $s, string $ls, array $commonStrings, array $lowercaseStrings): void
	{
		assertType('string', implode($s, $commonStrings));
		assertType('string', implode($s, $lowercaseStrings));
		assertType('string', implode($ls, $commonStrings));
		assertType('lowercase-string', implode($ls, $lowercaseStrings));
	}
}
