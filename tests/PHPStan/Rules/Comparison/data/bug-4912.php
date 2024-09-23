<?php

namespace Bug4912;

function funcB(): void
{
	throw new \RuntimeException();
}

function funcA(): void
{
	$a = null;

	try {
		$a = rand(0, 1);

		if (!$a) {
			throw new \RuntimeException();
		}

		funcB();
	} catch (\RuntimeException $e) {
		if ($a) {
			echo $a;
		}
	}
}
