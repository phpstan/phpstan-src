<?php // lint >= 8.1

namespace Bug11313;

enum Foo: string
{
	case CaseOne = 'one';
	case CaseTwo = 'two';
}

enum Bar: string
{
	case CaseThree = 'Three';
}

function test(Foo|Bar $union): bool
{
	return match ($union) {
		Bar::CaseThree,
		Foo::CaseOne => true,
		Foo::CaseTwo => false,
	};
}
