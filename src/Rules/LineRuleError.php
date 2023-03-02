<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/** @api */
interface LineRuleError extends RuleError
{

	public function getLine(): int;

}
