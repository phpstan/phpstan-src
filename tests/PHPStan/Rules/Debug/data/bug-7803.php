<?php declare(strict_types = 1);

namespace Bug7803;

use function PHPStan\dumpType;

/** @param array<int, string> $headers */
function headers(array $headers): void
{
  if (count($headers) >= 4) {
	dumpType(count($headers));
	dumpType($headers);
	dumpType(count($headers));
  }
}
