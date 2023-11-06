<?php // lint >= 8.3

namespace ValueAssignedToClassConstantNativeType;

class Foo
{

	public const int FOO = 1;

	public const int BAR = 'bar';

}

class Bar
{

	/** @var int<1, max> */
	public const int FOO = 1;

	/** @var int<1, max> */
	public const int BAR = 0;

}
