<?php

namespace ConstantLooseComparison;

class Foo
{

	public function doFoo(string $s, string $i): void
	{
		if ($s == $i) {

		}
		if ($s != $i) {

		}
		if (0 == "0") {

		}

		if (0 == "1") {

		}
	}

	public function doFooBar(): void
	{
		if (0 == "1") {

		} elseif (0 == "0") { // always-true should not be reported because last condition

		}

		if (0 == "1") {

		} elseif (0 == "0") { // always-true should be reported, because another condition below

		} elseif (rand (0, 1)) {

		}

	}

	/**
	 * @param 3 $i
	 */
	public function doLiteralIntInPhpDoc(int $i)
	{
		if ($i != 3) {
			throw new \LogicException();
		}
	}

}
