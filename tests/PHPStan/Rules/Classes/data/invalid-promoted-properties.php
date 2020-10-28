<?php

namespace InvalidPromotedProperties;

class Foo
{

	public function __construct(public $i) {}

	public function doFoo(private $j): void
	{

	}

}

function (public $i): void {

};

$f = fn (public $i) => 'foo';

function foo(public $i): void
{

}

class Bar
{

	abstract public function __construct(public $i);

}

interface Baz
{

	function __construct(public $i);

}

class Lorem
{

	public function __construct(public ...$i) {}

}
