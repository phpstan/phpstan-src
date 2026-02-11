<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface TipRuleError extends RuleError
{

	public function getTip(): string;

}
