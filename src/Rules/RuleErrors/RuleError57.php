<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError57 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\IdentifierRuleError, \PHPStan\Rules\MetadataRuleError
{

	/** @var string */
	public $message;

	/** @var string */
	public $tip;

	/** @var string */
	public $identifier;

	/** @var mixed[] */
	public $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/**
	 * @return mixed[]
	 */
	public function getMetadata(): array
	{
		return $this->metadata;
	}

}
