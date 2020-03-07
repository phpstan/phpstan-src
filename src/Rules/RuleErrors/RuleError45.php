<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError45 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\FileRuleError, \PHPStan\Rules\TipRuleError, \PHPStan\Rules\MetadataRuleError
{

	/** @var string */
	public $message;

	/** @var string */
	public $file;

	/** @var string */
	public $tip;

	/** @var mixed[] */
	public $metadata;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getFile(): string
	{
		return $this->file;
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
