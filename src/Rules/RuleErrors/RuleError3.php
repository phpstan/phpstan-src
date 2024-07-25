<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError3 implements RuleError, LineRuleError
{

	public string $message;

	public int $line;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

}
