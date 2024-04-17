<?php

namespace CallToConstructorWithoutImpurePoints;

class Foo
{

	public function __construct()
	{
	}

}

function (): void {
	new Foo();
};
