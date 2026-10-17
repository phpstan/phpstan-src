<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError67.cpp')]
final class RuleError67 implements RuleError, LineRuleError, NonIgnorableRuleError
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
