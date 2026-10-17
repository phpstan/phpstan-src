<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/RuleError105.cpp')]
final class RuleError105 implements RuleError, TipRuleError, MetadataRuleError, NonIgnorableRuleError
{

	public string $message;

	public string $tip;

	/** @var mixed[] */
	public array $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
