<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/** @api */
interface RuleError
{

	public function getMessage(): string;

}
