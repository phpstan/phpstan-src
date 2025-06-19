<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;

interface FixableNodeRuleError extends RuleError
{

	public function getOriginalNode(): Node;

	/** @return callable(Node): Node */
	public function getNewNodeCallable(): callable;

}
