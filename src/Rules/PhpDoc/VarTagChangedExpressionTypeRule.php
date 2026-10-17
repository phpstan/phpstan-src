<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Rules\Rule;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * @implements Rule<VarTagChangedExpressionTypeNode>
 */
#[RegisteredRule(level: 2)]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/VarTagChangedExpressionTypeRule.cpp')]
final class VarTagChangedExpressionTypeRule implements Rule
{

	public function __construct(private VarTagTypeRuleHelper $varTagTypeRuleHelper)
	{
	}

	public function getNodeType(): string
	{
		return VarTagChangedExpressionTypeNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		return $this->varTagTypeRuleHelper->checkExprType($scope, $node->getExpr(), $node->getVarTag()->getType());
	}

}
