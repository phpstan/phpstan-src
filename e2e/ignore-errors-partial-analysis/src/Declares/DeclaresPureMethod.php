<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

final class DeclaresPureMethod
{

	public function value(): int
	{
		return pureFunction() + 1;
	}

}
