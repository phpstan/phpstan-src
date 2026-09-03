<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileDependenciesRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError337 implements RuleError, IdentifierRuleError, NonIgnorableRuleError, FileDependenciesRuleError
{

	public string $message;

	public string $identifier;

	/** @var list<string> */
	public array $fileDependencies;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return list<string>
	 */
	public function getFileDependencies(): array
	{
		return $this->fileDependencies;
	}

}
