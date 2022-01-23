<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NullType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<InArrowFunctionNode>
 */
class TooWideArrowFunctionReturnTypehintRule implements Rule
{

	public function getNodeType(): string
	{
		return InArrowFunctionNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$functionReturnType = $scope->getAnonymousFunctionReturnType();
		if ($functionReturnType === null || !$functionReturnType instanceof UnionType) {
			return [];
		}

		$arrowFunction = $node->getOriginalNode();
		if ($arrowFunction->returnType === null) {
			return [];
		}
		$expr = $arrowFunction->expr;
		if ($expr instanceof Node\Expr\YieldFrom || $expr instanceof Node\Expr\Yield_) {
			return [];
		}

		$returnType = $scope->getType($expr);
		if ($returnType instanceof NullType) {
			return [];
		}
		$messages = [];
		foreach ($functionReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'Anonymous function never returns %s so it can be removed from the return type.',
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->build();
		}

		return $messages;
	}

}
