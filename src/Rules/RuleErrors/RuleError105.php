<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError105 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\MetadataRuleError, \PHPStan\Rules\NonIgnorableRuleError
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
