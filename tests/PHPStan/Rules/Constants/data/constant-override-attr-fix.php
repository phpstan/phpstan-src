<?php // lint >= 8.6

namespace ConstantOverrideAttrFix;

class Foo
{

	public const FOO = 1;

	public const BAR = 2;

	public const BAZ = 3;

}

class Bar extends Foo
{

	public const FOO = 1;

	/** @var int */
	public const BAR = 2;

	public const BAZ = 3, LOREM = 4;

}

class Baz extends Foo
{

	#[\Override]
	public const FOO = 1;

	#[\Override]
	public const IPSUM = 5;

	#[\Deprecated, \Override]
	public const DOLOR = 6;

	#[\Override]
	public const SIT = 7, AMET = 8;

}
