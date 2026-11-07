<?php declare(strict_types = 1); // lint >= 8.1

namespace ExportedEnumCaseOrder;

enum Suit
{

	public function label(): string
	{
		return 'suit';
	}

	case Spades;

	case Hearts;

}
