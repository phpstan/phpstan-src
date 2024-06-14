<?php // lint >= 8.0

namespace Bug5351;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(?string $html): void
	{
		$html ?? throw new \Exception();
		assertType('string', $html);
	}

	/**
	 * @return never
	 */
	public function neverReturn() {
		throw new \Exception();
	}

	public function doBar(?string $html): void
	{
		$html ?? $this->neverReturn();
		assertType('string', $html);
	}

}
