<?php declare(strict_types = 1);

namespace Bug6000;

function (): void {
	/** @var array{psr-4?: array<string, string|string[]>, classmap?: list<string>} $data */
	$data = [];

	foreach ($data as $key => $value) {
		if ($key === 'classmap') {
			echo implode(', ', $value); // not working :(
			echo implode(', ', $data[$key]); // this works though?!
		}
	}
};
