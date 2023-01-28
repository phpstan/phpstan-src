<?php // lint >= 7.4

namespace IncompatibleArrowFunctionDefaultParameterType;

class Foo
{

	public function doFoo(): void
	{
		$f = fn (int $i = null) => '1';
		$g = fn (?int $i = null) => '1';
		$h = fn (int $i = 5) => '1';
		$i = fn (int $i = 'foo') => '1';
	}

}
