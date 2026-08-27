<?php declare(strict_types = 1);

namespace RobotLoaderE2e;

// Deliberately outside any Composer autoload mapping: only RobotLoader can find this class,
// by scanning the directory. PHPStan has to consult the bootstrap-registered loader for it.
class Discovered
{

	public function doFoo(): int
	{
		return 1;
	}

}
