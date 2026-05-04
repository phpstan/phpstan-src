<?php // lint >= 8.3

namespace OverridingConstantNativeTypes;

class Foo
{

	public const int A = 1;
	public const int|string B = 1;
	public const int|string C = 1;
	public const int D = 1;

}

class Bar extends Foo
{

	public const int A = 2;
	public const int|string B = 'foo';
	public const int C = 1;
	public const int|string D = 1;

}

class Lorem
{

	public const A = 1;
	public const int B = 1;

}

class Ipsum extends Lorem
{

	public const int A = 1;
	public const B = 1;

}

class PharChild extends \Phar
{

	const BZ2 = 'foo'; // error

	const int GZ = 1; // OK

	const int|string NONE = 1; // error

}
