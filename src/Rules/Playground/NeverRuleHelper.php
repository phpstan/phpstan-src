<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

class NeverRuleHelper
{

	/**
	 * @return list<Node>|false
	 */
	public function shouldReturnNever(ReturnStatementsNode $node, Type $returnType): array|false
	{
		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			return false;
		}

		if ($node->isGenerator()) {
			return false;
		}

		$other = [];
		foreach ($node->getExecutionEnds() as $executionEnd) {
			if ($executionEnd->getStatementResult()->isAlwaysTerminating()) {
				$executionEndNode = $executionEnd->getNode();
				if (!$executionEndNode instanceof Node\Stmt\Expression) {
					$other[] = $executionEnd->getNode();
					continue;
				}

				if ($executionEndNode->expr instanceof Node\Expr\Throw_) {
					continue;
				}

				$other[] = $executionEnd->getNode();
				continue;
			}

			return false;
		}

		return $other;
	}

}
