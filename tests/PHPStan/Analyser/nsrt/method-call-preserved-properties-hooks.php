<?php // lint >= 8.4

declare(strict_types = 1);

namespace MethodCallPreservedPropertiesHooks;

use function PHPStan\Testing\assertType;

/** Hooked properties: a hook runs code the summary does not follow. */
final class Hooked
{

	public bool $foo = false;

	public int $hooked = 0 {
		set {
			$this->foo = false;
			$this->hooked = $value;
		}
	}

	public function run(): void
	{
		$this->foo = true;
		$this->doNothing();
		assertType('bool', $this->foo);
	}

	private function doNothing(): void
	{
		echo 'x';
	}

}

/** In a final class only its own code writes a protected(set) property. */
final class FinalProtectedSet
{

	public protected(set) int $protectedSet = 0;

	public function run(): void
	{
		$this->protectedSet = 1;
		$this->log();
		assertType('1', $this->protectedSet);
	}

	private function log(): void
	{
		\error_log('x');
	}

}
