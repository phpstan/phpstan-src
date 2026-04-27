<?php

namespace Bug4352;

$foo = [rand()];
$found = false;

foreach ($foo as $b) {
	if ($b > 2) {
		$a = $b;
		$found = true;
	}
}

if ($found) {
	/** @var int $a */
	echo $a;
}

class A {
	public function foo(): void {
		$foo = [rand()];
		$found = false;

		foreach ($foo as $b) {
			if ($b > 2) {
				$a = $b;
				$found = true;
			}
		}

		if ($found) {
			/** @var int $a */
			echo $a;
		}
	}
}
