<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/** @api */
interface TipRuleError extends RuleError
{

	public function getTip(): string;

}
