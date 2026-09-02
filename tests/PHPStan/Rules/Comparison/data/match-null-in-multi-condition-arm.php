<?php // lint >= 8.1

declare(strict_types = 1);

namespace MatchNullInMultiConditionArm;

enum E
{

	case A;
	case B;
	case C;
	case D;

}

class Subject
{

	public function get(): ?E
	{
		return null;
	}

	public function nullFirstInArm(): bool
	{
		return match ($this->get()) {
			E::A, E::B => true,
			null, E::C, E::D => false,
		};
	}

	public function nullInFirstArm(): bool
	{
		return match ($this->get()) {
			null, E::A, E::B => true,
			E::C, E::D => false,
		};
	}

	public function nullOwnArmFirst(): bool
	{
		return match ($this->get()) {
			null => false,
			E::A, E::B => true,
			E::C, E::D => false,
		};
	}

}
