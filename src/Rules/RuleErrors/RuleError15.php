<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

class RuleError15 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\LineRuleError, \PHPStan\Rules\FileRuleError, \PHPStan\Rules\TipRuleError
{

	/** @var string */
	public $message;

	/** @var int */
	public $line;

	/** @var string */
	public $file;

	/** @var string */
	public $tip;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getTip(): string
	{
		return $this->tip;
	}

}
