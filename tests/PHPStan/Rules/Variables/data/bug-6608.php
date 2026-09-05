<?php declare(strict_types = 1);

try {
	$var = new \DateTime('nope');
} catch (\Throwable $e) {}

if (isset($e) || $var instanceof \DateTime) {
}
