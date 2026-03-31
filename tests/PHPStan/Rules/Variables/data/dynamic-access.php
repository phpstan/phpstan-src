<?php

namespace VariablesDynamicAccess;

final class Foo
{

	/** @var 'foo'|'bar'|'buz' */
	public $name;

	public function test(string $string, object $obj): void
	{
		$foo = 'bar';

		echo $$foo;
		echo $$string;
		echo $$obj;
		echo ${$this->name};
	}

	public function testScope(): void
	{
		$name1 = 'foo';
		$rand = rand();
		if ($rand === 1) {
			$foo = 1;
			$name = $name1;
		} elseif ($rand === 2) {
			$name = 'bar';
			$bar = 'str';
		} else {
			$name = 'buz';
		}

		if ($name === 'foo') {
			echo $$name; // ok
			echo $foo; // ok
			echo $bar; // not ok
		} elseif ($name === 'bar') {
			echo $$name; // ok
			echo $foo; // not ok
			echo $bar; // ok
		} else {
			echo $$name; // not ok
			echo $foo; // not ok
			echo $bar; // not ok
		}

		echo $$name; // ok for foo and bar but not buz
		echo $foo;
		echo $bar;
	}

}
