<?php declare(strict_types=1);

namespace Bug11598;

use function unserialize;

function(): void {
	[ // @phpstan-ignore offsetAccess.nonArray
		'bar' => $foo,
	] = '';

	// @phpstan-ignore offsetAccess.nonArray
	[
		'bar' => $foo,
	] = '';
};
