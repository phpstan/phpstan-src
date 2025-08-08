<?php declare(strict_types = 1);

namespace Bug11908;

$matches = false;
if (preg_match('/a/', '', $matches) !== false && $matches) {
	var_export($matches);
}
