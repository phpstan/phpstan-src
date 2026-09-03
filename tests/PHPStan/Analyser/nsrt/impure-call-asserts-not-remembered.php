<?php declare(strict_types = 1);

namespace ImpureCallAssertsNotRemembered;

use function PHPStan\Testing\assertType;

function doFoo(string $path): void
{
	// realpath() carries @phpstan-assert-if-true on $path but is impure - the
	// assert narrows $path, the call's own value is never remembered
	if (realpath($path)) {
		assertType('non-empty-string', $path);
		assertType('non-empty-string|false', realpath($path));
	}
	if (realpath($path) ?: null) {
		assertType('non-empty-string|false', realpath($path));
	}
}

class Foo
{

	/** @var resource|null */
	private $process = null;

	/**
	 * @phpstan-impure
	 * @phpstan-assert-if-true !null $this->process
	 */
	private function impureAssert(): bool
	{
		return $this->process !== null && (bool) microtime(true);
	}

	/**
	 * @phpstan-impure
	 * @phpstan-assert-if-true !null $foo->process
	 */
	private static function impureStaticAssert(self $foo): bool
	{
		return $foo->process !== null && (bool) microtime(true);
	}

	public function doMethod(): void
	{
		// the assert narrows $this->process, the impure call's own value is
		// never remembered
		if ($this->impureAssert()) {
			assertType('resource', $this->process);
			assertType('bool', $this->impureAssert());
		}
	}

	public function doStatic(self $foo): void
	{
		if (self::impureStaticAssert($foo)) {
			assertType('resource', $foo->process);
			assertType('bool', self::impureStaticAssert($foo));
		}
	}

	public function doNullsafe(?self $foo): void
	{
		if ($foo?->impureAssert()) {
			assertType('resource', $foo->process);
			assertType('bool', $foo?->impureAssert());
			assertType('bool', $foo->impureAssert());
		}
	}

}
