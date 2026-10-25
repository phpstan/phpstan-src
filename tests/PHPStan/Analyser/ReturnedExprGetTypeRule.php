<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Rules\Rule;

/**
 * Asks the type of a method's returned expression from the callback for the
 * method itself, after the walk of the body is complete. The test only checks
 * that this does not crash.
 *
 * @implements Rule<ClassMethod>
 */
class ReturnedExprGetTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$return = (new NodeFinder())->findFirstInstanceOf((array) $node->stmts, Return_::class);
		if ($return === null || $return->expr === null) {
			return [];
		}

		$scope->getType($return->expr);

		return [];
	}

}
