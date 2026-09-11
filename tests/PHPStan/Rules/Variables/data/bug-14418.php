<?php declare(strict_types = 1);

namespace Bug14418;

 function retry(): string {
	for ($try = 0; $try <= 3; $try++) {
		try {
			if (rand(0, 1)) {
				return 'OK';
			}
			throw new \Exception();
		} catch (\Exception $e) {
			continue;
		}
	}
	throw $e;
}
retry();
