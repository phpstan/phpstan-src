<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError73.cpp')]
final class RuleError73 implements RuleError, TipRuleError, NonIgnorableRuleError
{

	public string $message;

	public string $tip;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

}
