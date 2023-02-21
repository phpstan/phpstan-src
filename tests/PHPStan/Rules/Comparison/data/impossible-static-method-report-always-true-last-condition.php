<?php

namespace ImpossibleStaticMethodReportAlwaysTrueLastCondition;

use PHPStan\Tests\AssertionClass;

class Foo
{

	public function doFoo(int $i)
	{
		if (rand(0, 1)) {

		} elseif (AssertionClass::assertInt($i)) {

		}
	}

	public function doBar(int $i)
	{
		if (rand(0, 1)) {

		} elseif (AssertionClass::assertInt($i)) {

		} else {

		}
	}

}
