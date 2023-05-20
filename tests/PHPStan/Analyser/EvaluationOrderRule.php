<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Node>
 */
class EvaluationOrderRule implements Rule
{

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			$node instanceof Node\Expr\FuncCall
			&& $node->name instanceof Node\Name
		) {
			return [
				RuleErrorBuilder::message($node->name->toString())
					->identifier('tests.evaluationOrder')
					->build(),
			];
		}

		if ($node instanceof Node\Scalar\String_) {
			return [
				RuleErrorBuilder::message($node->value)
					->identifier('tests.evaluationOrder')
					->build(),
			];
		}

		return [];
	}

}
