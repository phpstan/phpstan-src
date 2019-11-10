<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

class RuleError11 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\LineRuleError, \PHPStan\Rules\TipRuleError
{

	/** @var string */
	public $message;

	/** @var int */
	public $line;

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

	public function getTip(): string
	{
		return $this->tip;
	}

}
