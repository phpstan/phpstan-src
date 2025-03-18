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

}
