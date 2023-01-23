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

}
