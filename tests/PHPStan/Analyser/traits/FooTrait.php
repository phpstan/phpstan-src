<?php declare(strict_types = 1);

namespace AnalyseTraits;

trait FooTrait
{

	public function doTraitFoo(): void
	{
		$this->doFoo();
	}

	public function conflictingMethodWithDifferentArgumentNames(string $string): void
	{
		$r = strpos($string, 'foo');
	}

}
