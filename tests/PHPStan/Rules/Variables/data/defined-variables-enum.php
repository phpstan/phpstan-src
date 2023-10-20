<?php // lint >= 8.1

declare(strict_types=1);

namespace DefinedVariablesEnum;

enum Foo
{
	case A;
	case B;
}

class HelloWorld
{
	public function sayHello(Foo $f): void
	{
		switch ($f) {
			case Foo::A:
				$i = 5;
				break;
			case Foo::B:
				$i = 6;
				break;
		}

		var_dump($i);
	}
}
