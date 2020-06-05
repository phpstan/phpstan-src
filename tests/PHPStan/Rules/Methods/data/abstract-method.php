<?php

namespace AbstractMethod;

abstract class Foo
{

	abstract public function doFoo(): void;

}

class Bar
{

	abstract public function doBar(): void;

}

interface Baz
{

	abstract public function doBar(): void;

}

trait X
{

	abstract public static function a(self $b): void;

}

class Y
{

	use X;

	public static function a(self $b): void {}

}
