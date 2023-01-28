<?php // lint >= 8.1

namespace StrictComparisonLastMatchArm;

class Foo
{

	public function doBaz(): void
	{
		$a = 'aaa';
		if (rand(0, 1)) {
			$a = 'bbb';
		}

		match (true) {
			$a === 'aaa' => 1,
			$a === 'bbb' => 2,
		};
	}

	public function doFoo(): void
	{
		$a = 'aaa';
		if (rand(0, 1)) {
			$a = 'bbb';
		}

		if ($a === 'aaa') {

		} elseif ($a === 'bbb') {

		}

		if ($a === 'aaa') {

		} elseif ($a === 'bbb') {

		} elseif ($a === 'ccc') {

		} else {

		}

		if ($a === 'aaa') {

		} elseif ($a === 'bbb') {

		} else {

		}
	}

	public function doIpsum(): void
	{
		$a = 'aaa';
		if (rand(0, 1)) {
			$a = 'bbb';
		}

		match (true) {
			$a === 'aaa' => 1,
			$a === 'bbb' => 2,
			default => new \Exception(),
		};
	}

}
