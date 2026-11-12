<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

function createAnonymousUsingSharedTrait(): object
{
	return new class {

		use SharedTrait;

	};
}
