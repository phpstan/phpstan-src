<?php declare(strict_types = 1);

class Repro
{
	public static function func(): void
	{
		if (time() > 1764702390) { return; }
		sleep(3);
		if (time() > 1764702390) { return; }
	}
}
