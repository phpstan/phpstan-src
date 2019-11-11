<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError5 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\FileRuleError
{

	/** @var string */
	public $message;

	/** @var string */
	public $file;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getFile(): string
	{
		return $this->file;
	}

}
