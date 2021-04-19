<?php

namespace MisleadingTypes;

class Foo
{

	public function misleadingBoolReturnType(): \MisleadingTypes\boolean
	{

	}

	public function misleadingIntReturnType(): \MisleadingTypes\integer
	{

	}

	public function misleadingMixedReturnType(): mixed
	{

	}

}

function () {
	$foo = new Foo();
	die;
};
