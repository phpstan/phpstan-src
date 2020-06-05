<?php

namespace MissingMethodImpl;

interface Foo
{

	public function doFoo();

}

abstract class Bar implements Foo
{

	public function doBar()
	{

	}

	abstract public function doBaz();

}

class Baz implements Foo
{

	public function doBar()
	{

	}

	abstract public function doBaz();

}

interface Lorem extends Foo
{

}

new class() implements Foo {

};
