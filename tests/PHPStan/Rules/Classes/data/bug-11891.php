<?php // lint >= 8.1

namespace Bug11891;

enum test:int {
	case A = 42;
	case B = self::A->value;
};
