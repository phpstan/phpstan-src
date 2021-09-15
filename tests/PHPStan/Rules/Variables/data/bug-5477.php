<?php

namespace Bug5477;

function foo(): int {
	while (true) {
		try {
			$transaction = 1;
			break;
		} catch (Throwable $e) {
			continue;
		}
	}

	return $transaction;
}
