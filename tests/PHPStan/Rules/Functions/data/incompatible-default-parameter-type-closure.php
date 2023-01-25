<?php // lint >= 7.4

namespace IncompatibleClosureDefaultParameterType;

class Foo
{

	public function doFoo(): void
	{
		$f = function (int $i = null) {
			return '1';
		};
		$g = function (?int $i = null) {
			return '1';
		};
		$h = function (int $i = 5) {
			return '1';
		};
		$i = function (int $i = 'foo') {
			return '1';
		};
	}

}
