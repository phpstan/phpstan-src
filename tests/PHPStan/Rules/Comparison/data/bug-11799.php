<?php declare(strict_types = 1);

namespace Bug11799;

function getSomeInput(): string { return random_bytes(100); }

function getSortBy(): string
{
	$sortBy = mb_strtolower(getSomeInput());

	if (! in_array($sortBy, ['publishDate', 'approvedAt', 'allowedValues'], true)) {
		// Do something
	}

	return $sortBy;
}
