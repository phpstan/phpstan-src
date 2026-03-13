<?php declare(strict_types = 1);

namespace Bug11906;

function func(): void {
	try {
		throw new \LogicException('test');
	} catch (\LogicException $e) {
		// This catch-block should cause line 7 to not be treated as an exit point
	} finally {
		if (getenv('FOO')) {
			return;
		}
	}
}
