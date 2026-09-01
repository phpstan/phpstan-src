<?php declare(strict_types = 1);

namespace PharRun;

final class Bar
{

	public function doBar(string $s): int
	{
		return strlen($s);
	}

}
