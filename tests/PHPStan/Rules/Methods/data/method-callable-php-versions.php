<?php // lint >= 8.1

namespace MethodCallablePhpVersions;

class Foo
{

	public function doFoo(): void
	{
	}

	public function doBar(): void
	{
		if (PHP_VERSION_ID >= 80100) {
			$f = $this->doFoo(...);
		}

		if (PHP_VERSION_ID < 80100) {
			$g = $this->doFoo(...);
		}

		$h = $this->doFoo(...);
	}

}
