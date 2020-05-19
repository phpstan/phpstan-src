<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError89 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\IdentifierRuleError, \PHPStan\Rules\NonIgnorableRuleError
{

	public string $message;

	public string $tip;

	public string $identifier;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
