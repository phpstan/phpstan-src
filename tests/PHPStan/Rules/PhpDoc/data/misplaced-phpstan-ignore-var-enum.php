<?php // lint >= 8.1

namespace MisplacedPhpStanIgnoreVarEnum;

enum Suit
{

	/**
	 * @phpstan-ignore-var
	 */
	case Hearts;

}
