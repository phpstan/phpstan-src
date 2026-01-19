<?php declare(strict_types = 1);

namespace Bug13353;

$name = 'wow';

if (!isset($$name)) {
	echo 'oh no';
}

function (): void {
	$name = 'wow';

	if (!isset($$name)) {
		echo 'oh no';
	}
};
