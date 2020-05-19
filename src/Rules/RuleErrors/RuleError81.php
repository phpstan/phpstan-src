<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError81 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\IdentifierRuleError, \PHPStan\Rules\NonIgnorableRuleError
{

	public string $message;

	public string $identifier;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
