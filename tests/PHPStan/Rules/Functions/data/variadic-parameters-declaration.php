<?php declare(strict_types=1);

namespace VariadicParametersDeclaration;

class HelloWorld
{
	public function multipleVariadicParams(int ...$p1, int ...$p2): void
	{
	}

	public function sayHello(\DateTimeImmutable $date, string ...$o, string $r): string
	{
		return 'Hello, ' . $date->format('j. n. Y');
	}

	public function variadicParamAtEnd(int $number, int ...$numbers): void
	{
	}
}

function variadicFunction(int ...$a, string $b): void
{
}
