<?php

namespace BooleanOrReportAlwaysTrueLastCondition;

class Foo
{

	public function doFoo(): void
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif ($a || rand(0, 1)) {

		}
	}

	public function doBar(): void
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif ($a || rand(0, 1)) {

		} else {

		}
	}

}

class Foo2
{

	public function doFoo(): void
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif (rand(0, 1) || $a) {

		}
	}

	public function doBar(): void
	{
		$a = 1;
		if (rand(0, 1)) {

		} elseif (rand(0, 1) || $a) {

		} else {

		}
	}

}

class Foo3
{

	/**
	 * @param \Throwable|\Traversable $o
	 */
	public function doFoo($o)
	{
		if (rand(0, 1)) {

		} elseif ($o instanceof \Throwable || $o instanceof \Traversable) {

		}
	}

	/**
	 * @param \Throwable|\Traversable $o
	 */
	public function doBar($o)
	{
		if (rand(0, 1)) {

		} elseif ($o instanceof \Throwable || $o instanceof \Traversable) {

		} else {

		}
	}

}
