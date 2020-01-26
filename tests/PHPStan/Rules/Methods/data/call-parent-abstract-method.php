<?php

namespace CallParentAbstractMethod;

interface Baz
{

	public function uninstall(): void;

}

abstract class Foo implements Baz
{
}

class Bar extends Foo
{

	public function uninstall(): void
	{
		parent::uninstall();
	}

}

abstract class Lorem
{

	abstract public function doFoo(): void;

}

class Ipsum extends Lorem
{

	public function doFoo(): void
	{
		parent::doFoo();
	}

}

abstract class Dolor extends Lorem
{

	public function doBar(): void
	{
		parent::doFoo();
	}

}

abstract class SitAmet
{

	abstract static function doFoo(): void;

}

function (): void {
	SitAmet::doFoo();
};

abstract class Consecteur
{

	public function doFoo()
	{
		static::doBar();
	}

	abstract public function doBar(): void;

}
