<?php declare(strict_types = 1);

namespace ResultCacheRelativePath\OptionalSrc;

// Reported at level 8 unless the optional excludePath keeps this directory out of the analysis,
// so the scenario fails if the entry stops excluding.
class ExcludedWithError
{

	public function getName(): string
	{
		return 1;
	}

}
