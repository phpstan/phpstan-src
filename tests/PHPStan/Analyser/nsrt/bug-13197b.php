<?php declare(strict_types = 1);

namespace Bug13197b;

use function PHPStan\Testing\assertType;

function execute(string $command): void
{
	if (!function_exists('proc_open')) {
		return;
	}

	$pipes = [];

	$process = @proc_open(
		$command,
		[
			['pipe', 'rb'],
			3 => ['pipe', 'wb'], // stdout
			5 => ['pipe', 'wb'], // stderr
		],
		$pipes
	);

	assertType('array<0|3|5, resource>', $pipes);
}
