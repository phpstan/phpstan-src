<?php

namespace ConsistentConstructor;

/** @phpstan-consistent-constructor */
class Bar
{
	public function __construct(string $b) {}
}

class Bar2 extends Bar
{
	public function __construct(int $b) {}
}


class Foo
{
	public function __construct() {}
}

/** @phpstan-consistent-constructor */
class Foo1 extends Foo
{
	public function __construct(int $a)
	{
	}
}

class Foo2 extends Foo1
{
	public function __construct()
	{
	}
}
