<?php

namespace ParameterContravariance;

class Foo
{

	public function doFoo(\Exception $e)
	{

	}

	public function doBar(\InvalidArgumentException $e)
	{

	}

}

class Bar extends Foo
{

	public function doFoo(?\Exception $e)
	{

	}

	public function doBar(\Exception $e)
	{

	}

}

class Baz extends Foo
{

	public function doBar(?\Exception $e)
	{

	}

}

class Lorem extends Foo
{

	public function doFoo(\InvalidArgumentException $e)
	{

	}

}

class Ipsum extends Foo
{

	public function doFoo(?\InvalidArgumentException $e)
	{

	}

}

class MixedExplicitnessFoo
{

	public function setValue($value): void
	{

	}

}

class MixedExplicitnessBar extends MixedExplicitnessFoo
{

	public function setValue(mixed $value): void
	{

	}

}
