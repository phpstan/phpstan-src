<?php declare(strict_types = 1);

namespace PHPStan\Rules;

interface IdentifierRuleError extends RuleError
{

	public function getIdentifier(): string;

}
