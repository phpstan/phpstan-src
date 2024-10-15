<?php declare(strict_types = 1);

namespace Bug6807;

/** @return int */
function test()
{
	for ($attempts = 0; ; $attempts++)
	{
		if ($attempts > 5)
			throw new Exception();

		if (rand() == 1)
			return 5;
	}
}
