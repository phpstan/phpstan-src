<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FinallyExitPointsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function sprintf;

/**
 * @implements Rule<FinallyExitPointsNode>
 */
class OverwrittenExitPointByFinallyRule implements Rule
{

	public function getNodeType(): string
	{
		return FinallyExitPointsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (count($node->getTryCatchExitPoints()) === 0) {
			return [];
		}

		$errors = [];
		foreach ($node->getTryCatchExitPoints() as $exitPoint) {
			$errors[] = RuleErrorBuilder::message(sprintf('This %s is overwritten by a different one in the finally block below.', $this->describeExitPoint($exitPoint->getStatement())))
				->line($exitPoint->getStatement()->getStartLine())
				->identifier('finally.exitPoint')
				->build();
		}

		foreach ($node->getFinallyExitPoints() as $exitPoint) {
			$errors[] = RuleErrorBuilder::message(sprintf('The overwriting %s is on this line.', $this->describeExitPoint($exitPoint->getStatement())))
				->line($exitPoint->getStatement()->getStartLine())
				->identifier('finally.exitPoint')
				->build();
		}

		return $errors;
	}

	private function describeExitPoint(Node\Stmt $stmt): string
	{
		if ($stmt instanceof Node\Stmt\Return_) {
			return 'return';
		}

		if ($stmt instanceof Node\Stmt\Expression && $stmt->expr instanceof Node\Expr\Throw_) {
			return 'throw';
		}

		if ($stmt instanceof Node\Stmt\Continue_) {
			return 'continue';
		}

		if ($stmt instanceof Node\Stmt\Break_) {
			return 'break';
		}

		return 'exit point';
	}

}
