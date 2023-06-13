<?php // lint >= 8.1

namespace Bug9402;

enum Foo: int
{

	private const MY_CONST = 1;
	private const MY_CONST_STRING = 'foo';

	case Zero = 0;
	case One = self::MY_CONST;
	case Two = self::MY_CONST_STRING;

}
