<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError85 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\FileRuleError, \PHPStan\Rules\IdentifierRuleError, \PHPStan\Rules\NonIgnorableRuleError
{

	public string $message;

	public string $file;

	public string $identifier;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
