<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError65 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\NonIgnorableRuleError
{

	public string $message;

	public function getMessage(): string
	{
		return $this->message;
	}

}
