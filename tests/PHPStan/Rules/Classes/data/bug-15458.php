<?php // lint >= 8.2

namespace Bug15458;

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
