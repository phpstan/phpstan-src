<?php declare(strict_types=1);

namespace Bug6979;

foreach ([PHP_INT_MIN, 0, PHP_INT_MAX] as $v) {
	if ($v !== PHP_INT_MIN) {
		$invalidNumbers[] = -$v;
	}
}
