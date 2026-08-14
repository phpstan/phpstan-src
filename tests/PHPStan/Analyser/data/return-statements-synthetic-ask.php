<?php declare(strict_types = 1);

namespace ReturnStatementsSyntheticAsk;

function foo(): int
{
	return 1;
}

class Bar
{

	public function baz(): int
	{
		return 2;
	}

}
