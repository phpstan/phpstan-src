<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError33 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\MetadataRuleError
{

	/** @var string */
	public $message;

	/** @var array */
	public $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
