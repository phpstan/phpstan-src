<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Rules\NullsafeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ReturnStatementsNode>
 */
final class ReturnNullsafeByRefRule implements Rule
{

	public function __construct(private NullsafeCheck $nullsafeCheck)
	{
	}

	public function getNodeType(): string
	{
		return ReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->returnsByRef()) {
			return [];
		}

		$errors = [];
		foreach ($node->getReturnStatements() as $returnStatement) {
			$returnNode = $returnStatement->getReturnNode();
			if ($returnNode->expr === null) {
				continue;
			}

			if (!$this->nullsafeCheck->containsNullSafe($returnNode->expr)) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message('Nullsafe cannot be returned by reference.')
				->line($returnNode->getStartLine())
				->identifier('nullsafe.byRef')
				->nonIgnorable()
				->build();
		}

		return $errors;
	}

}
