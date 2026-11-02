<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\NodeVisitor;

interface FixableNodeRuleError extends RuleError
{

	public function getOriginalNode(): Node;

	/** @return callable(Node): (Node|NodeVisitor::REMOVE_NODE) */
	public function getNewNodeCallable(): callable;

}
