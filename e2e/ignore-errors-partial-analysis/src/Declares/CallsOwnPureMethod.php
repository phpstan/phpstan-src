<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

final class CallsOwnPureMethod
{

	public function value(): int
	{
		return pureFunction() + 1;
	}

	public function callValue(): void
	{
		$this->value();
	}

}
