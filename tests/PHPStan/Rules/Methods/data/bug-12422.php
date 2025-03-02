<?php // lint >= 8.1

namespace Bug12422;

enum MyEnum
{
	case A;
	case B;
}

class MyClass
{
	public function fooo(): void
	{
	}
}

function test(MyEnum $enum, ?MyClass $bar): void
{
	if ($enum === MyEnum::A && $bar === null) {
		return;
	}

	match ($enum) {
		MyEnum::A => $bar->fooo(),
		MyEnum::B => null,
	};
}
