<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

class UnmatchedInline
{

	public function doUnmatchedInline(): int
	{
		// @phpstan-ignore return.type
		return 1;
	}

}
