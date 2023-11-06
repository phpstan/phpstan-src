<?php // lint >= 8.3

namespace IncompatibleClassConstantPhpDocNativeType;

class Foo
{

	public const int FOO = 1;

	/** @var positive-int */
	public const int BAR = 1;

	/** @var non-empty-string */
	public const int BAZ = 1;

	/** @var int|string */
	public const int LOREM = 1;

}
