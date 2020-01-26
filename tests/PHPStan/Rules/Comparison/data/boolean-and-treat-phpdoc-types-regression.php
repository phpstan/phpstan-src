<?php

namespace BooleanAndTreatPhpDocTypesAsCertainRegression;

class Foo
{

	public function isDebugMode(): bool
	{
		return true;
	}

	public function something(): void
	{

	}

	public function doFoo(): void
	{
		$isDev = $this->isDebugMode();
		$used = false;

		while (true) {
			if ($isDev && $used) {
				return;
			}

			$used = true;
			$this->something();
		}
	}

}
