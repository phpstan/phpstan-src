<?php declare(strict_types = 1);

namespace PHPStan\Rules;

interface FixableNodeRuleError extends RuleError
{

	/** @return non-empty-list<NodeFix> */
	public function getNodeFixes(): array;

}
