<?php // lint >= 8.2

namespace Bug15458Types;

use function PHPStan\Testing\assertType;

enum E: string
{
	case A = self::A->value;
}

enum IntBacked: int
{
	case A = self::A->value;
}

enum Mutual: string
{
	case A = self::B->value;
	case B = self::A->value;
}

enum CrossA: string
{
	case A = CrossB::B->value;
}

enum CrossB: string
{
	case B = CrossA::A->value;
}

enum Concat: string
{
	case A = self::A->value . 'x';
}

enum ForwardReference: string
{
	case A = self::B->value . 'a';
	case B = 'b';
}

class ConstantConcat
{
	const A = self::A . 'x';
}

function (Mutual $mutual, ForwardReference $forwardReference): void {
	assertType('mixed', E::A->value);
	assertType('mixed', IntBacked::A->value);
	assertType('mixed', Mutual::A->value);
	assertType('mixed', Mutual::B->value);
	assertType('mixed', $mutual->value);
	assertType('mixed', CrossA::A->value);
	assertType('mixed', CrossB::B->value);
	assertType('non-falsy-string', Concat::A->value);
	assertType('non-falsy-string', ConstantConcat::A);
	assertType("'ba'", ForwardReference::A->value);
	assertType("'b'", ForwardReference::B->value);
	assertType("'b'|'ba'", $forwardReference->value);
};
