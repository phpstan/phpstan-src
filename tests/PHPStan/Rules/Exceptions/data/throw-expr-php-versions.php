<?php // lint >= 8.0

namespace ThrowExprPhpVersions;

class Bar
{

	public function doFoo(bool $b): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$b ? true : throw new \Exception();
		}

		if (PHP_VERSION_ID < 80000) {
			$b ? true : throw new \Exception();
		}

		$b ? true : throw new \Exception();
	}

}
