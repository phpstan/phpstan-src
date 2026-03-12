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

trait Baz
{
	case Active;
}

class BazConsumer
{
	use Baz;
}

enum Qux
{
	case Active;
}
