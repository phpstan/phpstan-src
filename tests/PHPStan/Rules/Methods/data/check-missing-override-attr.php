<?php

namespace CheckMissingOverrideAttr;

class Foo
{

	public function doFoo(): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): void
	{

	}

}

class ParentWithConstructor
{

	public function __construct() {}

}


class ChildOfParentWithConstructor extends ParentWithConstructor {

	public function __construct() {}

}

abstract class ParentWithAbstractConstructor
{

	abstract public function __construct();

}


class ChildOfParentWithAbstractConstructor extends ParentWithAbstractConstructor {

	public function __construct() {}

}
