<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/**
 * @api
 * @api-do-not-implement
 */
interface IdentifierRuleError extends RuleError
{

	public function getIdentifier(): string;

}
