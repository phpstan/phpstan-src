<?php declare(strict_types = 1);

namespace Bug12447;

$data[] = 1;

function (): void {
	$data[] = 1;
};
