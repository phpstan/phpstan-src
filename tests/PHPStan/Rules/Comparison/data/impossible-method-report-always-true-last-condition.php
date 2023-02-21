<?php

namespace ImpossibleMethodReportAlwaysTrueLastCondition;

use PHPStan\Tests\AssertionClass;

class Foo
{

	public function doFoo(string $s)
	{
		$assertion = new AssertionClass;
		if (rand(0, 1)) {

		} elseif ($assertion->assertString($s)) {

		}
	}

	public function doBar(string $s)
	{
		$assertion = new AssertionClass;
		if (rand(0, 1)) {

		} elseif ($assertion->assertString($s)) {

		} else {

		}
	}

}
