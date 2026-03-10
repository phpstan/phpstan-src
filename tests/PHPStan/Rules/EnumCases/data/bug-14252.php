<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14252;

class Foo
{
	case Active;
}

interface Bar
{
	case Active;
}

enum Qux
{
	case Active;
}
