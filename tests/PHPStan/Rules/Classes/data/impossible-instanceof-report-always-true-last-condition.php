<?php

namespace ImpossibleInstanceOfReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo(\Exception $e)
	{
		if (rand(0, 1)) {

		} elseif ($e instanceof \Exception) {

		}
	}

	public function doBar(\Exception $e)
	{
		if (rand(0, 1)) {

		} elseif ($e instanceof \Exception) {

		} else {

		}
	}

}
