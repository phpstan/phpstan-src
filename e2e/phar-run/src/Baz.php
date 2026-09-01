<?php declare(strict_types = 1);

namespace PharRun;

final class Baz
{

	public function doBaz(string $s): int
	{
		return strlen($s);
	}

}
