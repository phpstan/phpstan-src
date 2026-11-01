<?php declare(strict_types = 1);

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
