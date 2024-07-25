<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError17 implements RuleError, IdentifierRuleError
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
