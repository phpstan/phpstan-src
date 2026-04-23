<?php

declare(strict_types=1);

namespace PR5447Regression;

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
				assertType('int|null', $device);
			}
		}
	}

	public function nullable(): ?int
	{

	}

	/** @throws \Exception */
	private function throwsException(): int
	{

	}

}
