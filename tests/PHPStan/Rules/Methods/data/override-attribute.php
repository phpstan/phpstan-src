<?php

namespace OverrideAttribute;

class Foo
{

	public function test(): void
	{

	}

}

class Bar extends Foo
{

	#[\Override]
	public function test(): void
	{

	}

	#[\Override]
	public function test2(): void
	{

	}

}

class ParentWithConstructor
{

	public function __construct() {}

}


class ChildOfParentWithConstructor extends ParentWithConstructor {

	#[\Override]
	public function __construct() {}

}

abstract class ParentWithAbstractConstructor
{

	abstract public function __construct();

}


class ChildOfParentWithAbstractConstructor extends ParentWithAbstractConstructor {

	#[\Override]
	public function __construct() {}

}
