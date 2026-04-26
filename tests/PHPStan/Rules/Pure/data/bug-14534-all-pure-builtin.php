<?php declare(strict_types = 1);

namespace Bug14534AllPureBuiltin;

/**
 * @phpstan-pure
 */
function getResultCode(\Memcached $m): int
{
	return $m->getResultCode();
}
