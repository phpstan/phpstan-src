<?php declare(strict_types = 1);

namespace Bug12852;

function test(): void
{
	label:
	$foo = false;

	if (!$foo) {
		$foo = true;
		goto label;
	}
}
