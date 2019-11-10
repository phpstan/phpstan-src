<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

class RuleError1 implements \PHPStan\Rules\RuleError
{

	/** @var string */
	public $message;

	public function getMessage(): string
	{
		return $this->message;
	}

}
