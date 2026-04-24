<?php

namespace TryCatchReassignMethodNarrowing;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$device = $this->nullable();
		if ($device === null) {
			$device = 1;
			try {
				$device = $this->throwsException();
			} catch (\Exception) {
				$device = $this->nullable();
				// After reassignment to a fresh `$this->nullable()` call inside the catch,
				// the variable's type must be the method's declared return type. Earlier
				// conditional-expression holders stored at the initial `$device = $this->nullable()`
				// (narrowing `$this->nullable()` to `null` when `$device` is null) must not leak
				// through to this re-evaluation: each call may return a different value.
				assertType('int|null', $device);
			}
		}
	}

	public function nullable(): ?int
	{
		throw new \Exception();
	}

	/** @throws \Exception */
	private function throwsException(): int
	{
		throw new \Exception();
	}

}
