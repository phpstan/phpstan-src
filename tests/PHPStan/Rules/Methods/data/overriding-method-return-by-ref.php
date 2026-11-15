<?php

namespace OverridingMethodReturnByRef;

class Foo
{

	public function &byRef(): array
	{
		static $a = [];
		return $a;
	}

	public function byValue(): array
	{
		return [];
	}

}

class Bar extends Foo
{

	public function byRef(): array
	{
		return [];
	}

	public function &byValue(): array
	{
		static $a = [];
		return $a;
	}

}

class Baz extends Foo
{

	public function &byRef(): array
	{
		static $a = [];
		return $a;
	}

}

interface ByRefInterface
{

	public function &get(): array;

}

class ImplementsByRefInterface implements ByRefInterface
{

	public function get(): array
	{
		return [];
	}

}
