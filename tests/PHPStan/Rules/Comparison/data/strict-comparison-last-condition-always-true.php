<?php // lint >= 7.4

namespace StrictComparisonLastConditionAlwaysTrue;

class Foo
{

	public function doFoo(): void
	{
		$a = rand() ? 'foo' : 'bar';
		if (rand()) {

		} elseif ($a === 'foo') {

		} elseif ($a === 'bar') {

		} else {

		}
	}

	public function doFoo2(): void
	{
		$a = rand() ? 'foo' : 'bar';
		if (rand()) {

		} elseif ($a === 'foo') {

		} elseif ($a === 'bar') {

		}
	}

}
