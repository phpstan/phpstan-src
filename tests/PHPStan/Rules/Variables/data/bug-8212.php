<?php declare(strict_types = 1);

namespace Bug8212;

function test(): void
{

	$foo = rand();
	if ($foo) {
		$var = rand();
	}

// 200 lines later:

	if ($foo) {
		echo $var; // Variable $var might not be defined.
	}
}
