<?php // lint >= 8.1

namespace MatchUnhandledTrueWithComplexCondition;

enum Bar
{

	case ONE;
	case TWO;
	case THREE;

}

class Foo
{

	public Bar $type;

	public function getRand(): int
	{
		return rand(0, 10);
	}

	public function getPriority(): int
	{
		return match (true) {
			$this->type === Bar::ONE => 0,
			$this->type === Bar::TWO && $this->getRand() !== 8 => 1,
			$this->type === BAR::THREE => 2,
			$this->type === BAR::TWO => 3,
		};
	}

}
