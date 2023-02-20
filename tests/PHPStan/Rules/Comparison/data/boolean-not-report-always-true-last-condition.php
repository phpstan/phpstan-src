<?php

namespace BooleanNotReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo()
	{
		$a = 0;
		if (rand(0, 1)) {

		} elseif (!$a) {

		}
	}

	public function doBar()
	{
		$a = 0;
		if (rand(0, 1)) {

		} elseif (!$a) {

		} else {

		}
	}

}

class Bar
{

	public function doFoo()
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif (!$a) {

		}
	}

	public function doBar()
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif (!$a) {

		} else {

		}
	}

}
