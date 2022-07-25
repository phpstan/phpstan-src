<?php

namespace Bug2112;

use function PHPStan\Testing\assertType;

class Foo
{

	public function getFoos(): array
	{
		return [];
	}

	public function doBar(): void
	{
		$foos = $this->getFoos();
		assertType('array', $foos);
		$foos[0] = null;

		assertType('null', $foos[0]);
		assertType('hasOffsetValue(0, null)&non-empty-array', $foos);
	}

	/** @return self[] */
	public function getFooBars(): array
	{
		return [];
	}

	public function doBars(): void
	{
		$foos = $this->getFooBars();
		assertType('array<Bug2112\Foo>', $foos);
		$foos[0] = null;

		assertType('null', $foos[0]);
		assertType('non-empty-array<Bug2112\Foo|null>&hasOffsetValue(0, null)', $foos);
	}

}
