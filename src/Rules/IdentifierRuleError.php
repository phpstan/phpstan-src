<?php declare(strict_types = 1);

namespace PHPStan\Rules;

/** @api */
interface IdentifierRuleError extends RuleError
{

	public function getIdentifier(): string;

}
