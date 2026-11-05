<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class ReturnStatementsNodeParserNodeRule implements Rule
{

	public function getNodeType(): string
	{
		return ReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof MethodReturnStatementsNode) {
			$parserNode = $node->getClassMethod();
		} elseif ($node instanceof FunctionReturnStatementsNode) {
			$parserNode = $node->getFunction();
		} else {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'%s %s(), lines %d-%d, return type node: %s',
				$parserNode->getType(),
				$parserNode->name->toString(),
				$parserNode->getStartLine(),
				$parserNode->getEndLine(),
				$parserNode->returnType !== null ? $parserNode->returnType->getType() : 'none',
			))->identifier('tests.returnStatementsNodeParserNode')->build(),
		];
	}

}
