<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug12722;

enum states {
	case state1;
	case statealmost1;
	case state3;
}

class HelloWorld
{
	public function intentional_fallthrough(states $state): int
	{
		switch($state) {

			case states::state1: //intentional fall-trough this case...
			case states::statealmost1: return 1;
			case states::state3: return 3;
		}
	}

	public function no_fallthrough(states $state): int
	{
		switch($state) {

			case states::state1: return 1;
			case states::statealmost1: return 1;
			case states::state3: return 3;
		}
	}
}
