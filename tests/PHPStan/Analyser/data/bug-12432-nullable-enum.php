<?php

namespace Bug12432;

use function PHPStan\Testing\assertType;

enum Foo: int
{
	case BAR = 1;
	case BAZ = 2;
	case QUX = 3;
}

function requireNullableEnum(?Foo $nullable): ?Foo
{
	switch ($nullable) {
		case Foo::BAR:
			assertType('Bug12432\Foo::BAR', $nullable);
		case Foo::BAZ:
			assertType('Bug12432\Foo::BAR|Bug12432\Foo::BAZ', $nullable);
			break;
		case '':
			assertType('null', $nullable);
		case null:
			assertType('null', $nullable);
			break;
		case 0:
			assertType('*NEVER*', $nullable);
		default:
			assertType('Bug12432\Foo::QUX', $nullable);
			break;
	}

	return $nullable;
}
