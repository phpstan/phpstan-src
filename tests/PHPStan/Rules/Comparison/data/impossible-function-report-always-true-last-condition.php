<?php

namespace ImpossibleFunctionReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo(int $i)
	{
		if (rand(0, 1)) {

		} elseif (is_int($i)) {

		}
	}

	public function doBar(int $i)
	{
		if (rand(0, 1)) {

		} elseif (is_int($i)) {

		} else {

		}
	}

}
