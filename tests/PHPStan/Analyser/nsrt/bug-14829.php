<?php declare(strict_types = 1);

namespace Bug14829;

use function PHPStan\Testing\assertType;

class Checker
{

	/** @phpstan-assert-if-true =non-empty-string $path */
	public function isReadable(string $path): bool
	{
		return $path !== '';
	}

	/** @phpstan-assert-if-true =non-empty-string $path */
	public static function staticIsReadable(string $path): bool
	{
		return $path !== '';
	}

}

function testFunction(string $path): void
{
	// is_readable() has @phpstan-assert-if-true in stubs/file.stub
	if (is_readable($path)) {
		assertType('true', is_readable($path));
		assertType('non-empty-string', $path);
	}

	if (!is_readable($path)) {
		return;
	}
	assertType('true', is_readable($path));
}

function testMethod(Checker $c, string $path): void
{
	if ($c->isReadable($path)) {
		assertType('true', $c->isReadable($path));
		assertType('non-empty-string', $path);
	}
}

function testStaticMethod(string $path): void
{
	if (Checker::staticIsReadable($path)) {
		assertType('true', Checker::staticIsReadable($path));
		assertType('non-empty-string', $path);
	}
}
