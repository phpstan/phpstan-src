<?php declare(strict_types = 1);

namespace Bug15184;

function check(object $o): bool
{
	return $o instanceof \Bug15184\Type\optional;
}
