<?php

namespace DoWhileLoopConstantCondition;

class Foo
{

	public function doFoo()
	{
		do {

		} while (true); // report
	}

	public function doFoo2()
	{
		do {
			if (rand(0, 1)) {
				return;
			}
		} while (true); // do not report
	}

	public function doFoo3()
	{
		do {
			if (rand(0, 1)) {
				break;
			}
		} while (true); // do not report
	}

	public function doBar()
	{
		do {

		} while (false); // report
	}

	public function doBar2()
	{
		do {
			if (rand(0, 1)) {
				return;
			}
		} while (false);  // report
	}

	public function doBar3()
	{
		do {
			if (rand(0, 1)) {
				break;
			}
		} while (false); // report
	}

	public function doFoo4()
	{
		do {
			if (rand(0, 1)) {
				continue;
			}
		} while (true); // report
	}

	public function doBar4()
	{
		do {
			if (rand(0, 1)) {
				continue;
			}
		} while (false); // report
	}

	public function doFoo5(array $a)
	{
		foreach ($a as $v) {
			do {
				if (rand(0, 1)) {
					continue 2;
				}
			} while (true); // do not report
		}
	}

	public function doFoo6(array $a)
	{
		foreach ($a as $v) {
			do {
				if (rand(0, 1)) {
					break 2;
				}
			} while (true); // do not report
		}
	}

}
