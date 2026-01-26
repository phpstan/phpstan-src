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

class ResultA {
	public function __construct(public string $value) {}
}

class ResultB extends ResultA {
	public function rot13(): string { return str_rot13($this->value); }
}

/** @template-implements I<ResultB> */
class In implements I {
	public const string ResultType = ResultB::class;
}

/** @template T of ResultA */
interface I {
	/** @var class-string<T> */
	public const string ResultType = ResultA::class;
}
