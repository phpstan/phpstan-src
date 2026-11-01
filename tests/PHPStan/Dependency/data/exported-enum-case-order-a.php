<?php declare(strict_types = 1);

namespace ExportedEnumCaseOrder;

enum Suit
{

	case Hearts;

	case Spades;

	public function label(): string
	{
		return 'suit';
	}

}
