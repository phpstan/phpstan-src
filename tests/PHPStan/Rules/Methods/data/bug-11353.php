<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11353;

enum Identifier: string
{
	case A = 'identifier_a';
	case B = 'identifier_b';
	case C = 'identifier_c';
}

class A
{
	public static function create(): self
	{
		return new self();
	}
}

class B
{
	public static function create(): self
	{
		return new self();
	}
}

class C
{
	// missing static function create(): self
}

class SomeCliCommand
{
	public function execute(string $input): void
	{
		$identifier = Identifier::from($input);

		$classFQCN = match ($identifier) {
			Identifier::A => A::class,
			Identifier::B => B::class,
			Identifier::C => C::class,
		};

		// No warning that C::create() does not exist
		$class = $classFQCN::create();
	}
}
