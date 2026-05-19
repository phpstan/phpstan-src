<?php declare(strict_types = 1);

namespace Bug12167;

function retryPattern(): void
{
	$attempt = 0;

	try {
		retry:
		$attempt++;
		if (mt_rand(0,1) === 1) {
			throw new \RuntimeException;
		}
	} catch(\RuntimeException $e) {
		if ($attempt < 4) {
			goto retry;
		}
		throw $e;
	}
}
