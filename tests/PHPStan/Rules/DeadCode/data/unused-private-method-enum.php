<?php // lint >= 8.1

namespace UnusedPrivateMethodEnunm;

enum Foo
{

	public function doFoo(): void
	{
		$this->doBar();
	}

	private function doBar(): void
	{

	}

	private function doBaz(): void
	{

	}

}
