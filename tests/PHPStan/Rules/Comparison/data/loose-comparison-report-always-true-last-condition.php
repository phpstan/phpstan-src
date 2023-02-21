<?php

namespace LooseComparisonReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo()
	{
		if (rand(0, 1)) {

		} elseif (1 == 1) {

		}
	}

	public function doBar()
	{
		if (rand(0, 1)) {

		} elseif (1 == 1) {

		} else {

		}
	}

}
