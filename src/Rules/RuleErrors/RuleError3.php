<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

class RuleError3 implements \PHPStan\Rules\RuleError, \PHPStan\Rules\LineRuleError
{

	/** @var string */
	public $message;

	/** @var int */
	public $line;

	public function getMessage(): string
	{
		return $this->message;
	}

	public function getLine(): int
	{
		return $this->line;
	}

}
