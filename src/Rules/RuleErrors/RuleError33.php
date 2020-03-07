<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError33 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\MetadataRuleError
{

	/** @var string */
	public $message;

	/** @var mixed[] */
	public $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
