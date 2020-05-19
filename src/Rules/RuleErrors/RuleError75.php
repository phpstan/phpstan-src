<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError75 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\LineRuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\NonIgnorableRuleError
{

	public string $message;

	public int $line;

	public string $tip;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

}
