<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PhpParser\Node;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

final class NeverRuleHelper
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
				if (!$executionEnd->getNode() instanceof Node\Stmt\Throw_) {
					$other[] = $executionEnd->getNode();
				}

				continue;
			}

			return false;
		}

		return $other;
	}

}
